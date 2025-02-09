<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Node;

use ArrayIterator;
use Behat\Gherkin\Exception\NodeException;
use Iterator;
use IteratorAggregate;
use ReturnTypeWillChange;

/**
 * Represents Gherkin Table argument.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @phpstan-type TCell string
 * @phpstan-type TRowLine int
 * @phpstan-type TRow list<TCell>
 * @phpstan-type TTable array<TRowLine, TRow>
 * @phpstan-type THash array<string, TCell>
 *
 * @phpstan-implements IteratorAggregate<int, THash>
 */
class TableNode implements ArgumentInterface, IteratorAggregate
{
    /**
     * @var array<array-key, int>
     */
    private array $maxLineLength = [];

    /**
     * Initializes table.
     *
     * @phpstan-param TTable $table Table in form of [$rowLineNumber => [$val1, $val2, $val3]]
     *
     * @throws NodeException If the given table is invalid
     */
    public function __construct(
        private array $table,
    ) {
        $columnCount = null;

        foreach ($table as $rowLine => $row) {
            // @phpstan-ignore function.alreadyNarrowedType
            if (!is_array($row)) {
                throw new NodeException(sprintf(
                    "Table row '%s' is expected to be array, got %s",
                    $rowLine,
                    gettype($row)
                ));
            }

            if ($columnCount === null) {
                $columnCount = count($row);
            }

            if (count($row) !== $columnCount) {
                throw new NodeException(sprintf(
                    "Table row '%s' is expected to have %s columns, got %s",
                    $rowLine,
                    $columnCount,
                    count($row)
                ));
            }

            foreach ($row as $column => $cell) {
                if (!isset($this->maxLineLength[$column])) {
                    $this->maxLineLength[$column] = 0;
                }

                $this->maxLineLength[$column] = max($this->maxLineLength[$column], mb_strlen($cell, 'utf8'));
            }
        }
    }

    /**
     * Creates a table from a given list.
     *
     * @phpstan-param array<int, TCell> $list One-dimensional array
     *
     * @return TableNode
     *
     * @throws NodeException If the given list is not a one-dimensional array
     */
    public static function fromList(array $list)
    {
        if (count($list) !== count($list, COUNT_RECURSIVE)) {
            throw new NodeException('List is not a one-dimensional array.');
        }

        return new self(array_map(static fn ($item) => [$item], $list));
    }

    public function getNodeType()
    {
        return 'Table';
    }

    /**
     * Returns table hash, formed by columns (ColumnsHash).
     *
     * @phpstan-return list<THash>
     */
    public function getHash()
    {
        return $this->getColumnsHash();
    }

    /**
     * Returns table hash, formed by columns.
     *
     * @phpstan-return list<THash>
     */
    public function getColumnsHash()
    {
        $rows = $this->getRows();
        $keys = array_shift($rows);

        $hash = [];
        foreach ($rows as $row) {
            \assert($keys !== null); // If there is no first row due to an empty table, we won't enter this loop either.
            $hash[] = array_combine($keys, $row);
        }

        return $hash;
    }

    /**
     * Returns table hash, formed by rows.
     *
     * @phpstan-return array<TCell, TCell|TRow>
     */
    public function getRowsHash()
    {
        $hash = [];

        foreach ($this->getRows() as $row) {
            $hash[array_shift($row)] = count($row) === 1 ? $row[0] : $row;
        }

        return $hash;
    }

    /**
     * Returns numerated table lines.
     * Line numbers are keys, lines are values.
     *
     * @phpstan-return TTable
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Returns table rows.
     *
     * @phpstan-return list<TRow>
     */
    public function getRows()
    {
        return array_values($this->table);
    }

    /**
     * Returns table definition lines.
     *
     * @phpstan-return list<TRowLine>
     */
    public function getLines()
    {
        return array_keys($this->table);
    }

    /**
     * Returns specific row in a table.
     *
     * @param int $index Row number
     *
     * @phpstan-return TRow
     *
     * @throws NodeException If row with specified index does not exist
     */
    public function getRow($index)
    {
        $rows = $this->getRows();

        if (!isset($rows[$index])) {
            throw new NodeException(sprintf('Rows #%d does not exist in table.', $index));
        }

        return $rows[$index];
    }

    /**
     * Returns specific column in a table.
     *
     * @param int $index Column number
     *
     * @phpstan-return list<TCell>
     *
     * @throws NodeException If column with specified index does not exist
     */
    public function getColumn($index)
    {
        if ($index >= count($this->getRow(0))) {
            throw new NodeException(sprintf('Column #%d does not exist in table.', $index));
        }

        $rows = $this->getRows();
        $column = [];

        foreach ($rows as $row) {
            $column[] = $row[$index];
        }

        return $column;
    }

    /**
     * Returns line number at which specific row was defined.
     *
     * @param int $index
     *
     * @phpstan-return TRowLine
     *
     * @throws NodeException If row with specified index does not exist
     */
    public function getRowLine($index)
    {
        $lines = array_keys($this->table);

        if (!isset($lines[$index])) {
            throw new NodeException(sprintf('Rows #%d does not exist in table.', $index));
        }

        return $lines[$index];
    }

    /**
     * Converts row into delimited string.
     *
     * @param int $rowNum Row number
     *
     * @return string
     */
    public function getRowAsString($rowNum)
    {
        $values = [];
        foreach ($this->getRow($rowNum) as $column => $value) {
            $values[] = $this->padRight(' ' . $value . ' ', $this->maxLineLength[$column] + 2);
        }

        return sprintf('|%s|', implode('|', $values));
    }

    /**
     * Converts row into delimited string.
     *
     * @param int $rowNum Row number
     * @param callable(string, int): string $wrapper Wrapper function
     *
     * @return string
     */
    public function getRowAsStringWithWrappedValues($rowNum, $wrapper)
    {
        $values = [];
        foreach ($this->getRow($rowNum) as $column => $value) {
            $value = $this->padRight(' ' . $value . ' ', $this->maxLineLength[$column] + 2);

            $values[] = $wrapper($value, $column);
        }

        return sprintf('|%s|', implode('|', $values));
    }

    /**
     * Converts entire table into string.
     *
     * @return string
     */
    public function getTableAsString()
    {
        $lines = [];
        $rowCount = count($this->getRows());
        for ($i = 0; $i < $rowCount; ++$i) {
            $lines[] = $this->getRowAsString($i);
        }

        return implode("\n", $lines);
    }

    public function getLine()
    {
        return $this->getRowLine(0);
    }

    /**
     * Converts table into string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getTableAsString();
    }

    /**
     * Retrieves a hash iterator.
     */
    #[ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->getHash());
    }

    /**
     * Obtains and adds rows from another table to the current table.
     * The second table should have the same structure as the current one.
     *
     * @return void
     *
     * @deprecated remove together with OutlineNode::getExampleTable
     */
    public function mergeRowsFromTable(TableNode $node)
    {
        // check structure
        if ($this->getRow(0) !== $node->getRow(0)) {
            throw new NodeException('Tables have different structure. Cannot merge one into another');
        }

        $firstLine = $node->getLine();
        foreach ($node->getTable() as $line => $value) {
            if ($line === $firstLine) {
                continue;
            }

            $this->table[$line] = $value;
        }
    }

    /**
     * Pads string right.
     *
     * @param string $text Text to pad
     * @param int $length Length
     *
     * @return string
     */
    protected function padRight($text, $length)
    {
        while ($length > mb_strlen($text, 'utf8')) {
            $text .= ' ';
        }

        return $text;
    }
}
