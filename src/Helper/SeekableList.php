<?php
/**
 * @author roxblnfk
 * Date: 25.11.2018
 */

namespace roxblnfk\Soco\Helper;


class SeekableList
implements \SeekableIterator, \Countable
{
    /** @var mixed[] */
    protected $elements = [];
    /** @var int */
    protected $caret;

    public function __construct() {

    }

    /**
     * Return the current element
     * @return mixed Can return any type.
     */
    public function current() {
        if (isset($this->caret))
            return $this->elements[$this->caret] ?? null;
        return null;
    }
    /**
     * Move to previous entry
     * @return void
     */
    public function prev () {
        --$this->caret;
    }
    /**
     * Move forward to next element
     * @return void Any returned value is ignored.
     */
    public function next() {
        ++$this->caret;
    }
    /**
     * Return the key of the current element
     * @return mixed scalar on success, or null on failure.
     */
    public function key() {
        return $this->caret;
    }
    /**
     * Checks if current position is valid
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid() {
        return isset($this->caret, $this->elements[$this->caret]);
    }
    /**
     * Rewind the Iterator to the first element
     * @return void Any returned value is ignored.
     */
    public function rewind() {
        $this->caret = count($this->elements) ? array_key_first($this->elements) : null;
    }
    /**
     * Rewind the Iterator to the last element
     * @return void Any returned value is ignored.
     */
    public function wind()
    {
        $this->caret = $this->elements ? array_key_last($this->elements) : null;
    }

    /**
     * Count elements of an object
     * @return int The custom count as an integer.
     * The return value is cast to an integer.
     */
    public function count() {
        return count($this->elements);
    }

    /**
     * Seeks to a position
     * @param int $position <p>
     * The position to seek to.
     * @return void
     */
    public function seek($position) {
        if (!key_exists($position, $this->elements))
            throw new \OutOfBoundsException();
        $this->caret = $position;
    }
    /**
     * Return true if list is empty
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->elements) === 0;
    }
    /**
     * Append element without caret move
     * @param $element
     */
    public function push($element)
    {
        $this->elements[] = $element;
    }
    /**
     * Remove all elements and reset caret
     */
    public function clean()
    {
        $this->elements = [];
        $this->caret = null;
    }
}