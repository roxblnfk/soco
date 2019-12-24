<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Collection;

use roxblnfk\Soco\Gameplay\Action\AbstractAction;
use roxblnfk\Soco\Helper\SeekableList;

/**
 * @method AbstractAction current()
 */
class ActionHistory extends SeekableList
{
    /**
     * Add new value after current value. New value will be current
     * @param AbstractAction $value
     */
    public function sendSplice(AbstractAction $value) {
        if (is_null($this->caret)) {
            $this->elements = [$value];
            $this->caret = 0;
        } else {
            ++$this->caret;
            if (key_exists($this->caret, $this->elements)) {
                array_splice($this->elements, $this->caret);
            }
            $this->elements[$this->caret] = $value;
        }
    }
}
