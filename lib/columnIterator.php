<?php
/**
 * Column iterator for Cassandra
 *
 * Object that implements ITERATOR that will loop through all columns for a 
 * specific row key.
 *
 * @author Dave Gardner <dave@cruft.co>
 *
 * This file is part of We Have Your Kidneys.
 *
 * We Have Your Kidneys is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * We Have Your Kidneys is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with We Have Your Kidneys.  If not, see <http://www.gnu.org/licenses/>.
 */

class columnIterator implements Iterator
{
    /**
     * CF
     * 
     * @var ColumnFamily
     */
    private $cf;
    
    /**
     * Row key
     * 
     * @var string
     */
    private $rowKey;
    
    /**
     * Desired start column cut-off
     * 
     * @var string
     */
    private $startColumn;
    
    /**
     * Desired end column cut-off
     * 
     * @var string
     */
    private $endColumn;
    
    /**
     * Next column; the one to read next
     * 
     * @var string|NULL
     */
    private $nextColumn;
    
    /**
     * Buffer; lazy-initialised
     * 
     * @var array|NULL
     */
    private $buffer = NULL;
    
    /**
     * No more to fetch
     * 
     * @var boolean
     */
    private $noMoreToFetch = FALSE;
    
    /**
     * Constructor
     * 
     * @param ColumnFamily $columnFamily The CF to iterate a row for
     * @param string $rowKey The row key
     * @param string|NULL $startColumn The start column, NULL for open-ended
     * @param string|NULL $endColumn The end column, NULL for open-ended
     * @param 
     */
    public function __construct(
            ColumnFamily $columnFamily,
            $rowKey,
            $startColumn = '',
            $endColumn = ''
            )
    {
        $this->cf = $columnFamily;
        $this->rowKey = $rowKey;
        $this->startColumn = $startColumn;
        $this->endColumn = $endColumn;
        $this->nextColumn = $startColumn;
    }
        
    public function current()
    {
        $this->initBuffer();
        return current($this->buffer);
    }
    
    public function key()
    {
        $this->initBuffer();
        return key($this->buffer);
    }
    
    public function next()
    {
        $this->initBuffer();
        if (next($this->buffer) === FALSE) {
            $this->readBuffer();
        }
    }
    
    public function rewind()
    {
        $this->nextColumn = $this->startColumn;
        $this->buffer = NULL;
        $this->initBuffer();
    }
    
    public function valid()
    {
        $this->initBuffer();
        return current($this->buffer) !== FALSE;
    }
    
    /**
     * Init buffer
     * 
     * If buffer not initialised, we will do so now
     */
    private function initBuffer()
    {
        if ($this->buffer === NULL) {
            $this->buffer = array();
            $this->noMoreToFetch = FALSE;
            $this->readBuffer();
        }
    }
    
    /**
     * Read next from buffer
     */
    private function readBuffer()
    {
        if (!$this->noMoreToFetch) {
            $this->buffer = $this->cf->get(
                    $this->rowKey,      // row key
                    NULL,               // no specific cols
                    $this->nextColumn,
                    $this->endColumn
                    );
            // if call# > 1st, trim off first column
            if ($this->startColumn !== $this->nextColumn) {
                array_shift($this->buffer);
            }

            if (!empty($this->buffer)) {
                end($this->buffer);
                list($this->nextColumn) = each($this->buffer);
                reset($this->buffer);
            } else {
                $this->noMoreToFetch = TRUE;
            }
        }
    }
    
    
    /*
                $adId,                                  // row key
            NULL,                                   // no specific columns
            date('YmdH', strtotime('-2 hours')),    // start column
      
     *       NULL                                    // open-ended (no end col)
     */

}
