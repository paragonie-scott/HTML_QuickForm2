<?php
/**
 * Base class for HTML_QuickForm2 groups
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2006, 2007, Alexey Borzov <avb@php.net>,
 *                           Bertrand Mansion <golgote@mamasam.com>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * The names of the authors may not be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   HTML
 * @package    HTML_QuickForm2
 * @author     Alexey Borzov <avb@php.net>
 * @author     Bertrand Mansion <golgote@mamasam.com>
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/HTML_QuickForm2
 */

/**
 * Base class for all HTML_QuickForm2 containers
 */
require_once 'HTML/QuickForm2/Container.php';

/**
 * Base class for QuickForm2 groups of elements
 *
 * @category   HTML
 * @package    HTML_QuickForm2
 * @author     Alexey Borzov <avb@php.net>
 * @author     Bertrand Mansion <golgote@mamasam.com>
 * @version    Release: @package_version@
 */
class HTML_QuickForm2_Container_Group extends HTML_QuickForm2_Container
{
   /**
    * Group name
    * If set, group name will be used as prefix for contained
    * element names, like groupname[elementname].
    * @var string
    */
    protected $name;

   /**
    * Previous group name
    * Stores the previous group name when the group name is changed.
    * Used to restore children names if necessary.
    * @var string
    */
    protected $previousName;

    public function getType()
    {
        return 'group';
    }

    public function setValue($value)
    {
        throw new HTML_QuickForm2_Exception('Not implemented');
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->previousName = $this->name;
        $this->name = $name;
        foreach ($this as $child) {
            $this->renameChild($child);
        }
        return $this;
    }

    protected function renameChild(HTML_QuickForm2_Node $element)
    {
        $tokens = explode('[', str_replace(']', '', $element->getName()));
        if ($this === $element->getContainer()) {
            // Child has already been renamed by its group before
            if (!is_null($this->previousName) &&
                $this->previousName !== '') {
                $gtokens = explode('[', str_replace(']', '', $this->previousName));
                $pos = array_search(end($gtokens), $tokens);
                if (!is_null($pos)) {
                    $tokens = array_slice($tokens, $pos+1);
                }
            }
        }
        if (is_null($this->name) || $this->name === '') {
            if (is_null($this->previousName) || $this->previousName === '') {
                return $element;
            } else {
                $elname = $tokens[0];
                unset($tokens[0]);
                foreach ($tokens as $v) {
                    $elname .= '['.$v.']';
                }
            }
        } else {
            $elname = $this->getName().'['.implode('][', $tokens).']';
        }
        $element->setName($elname);
        return $element;
    }



    public function toggleFrozen($freeze = null)
    {
        if (null !== $freeze) {
            foreach ($this as $child) {
                $child->toggleFrozen($freeze);
            }
        }
        return parent::toggleFrozen($freeze);
    }

    public function persistentFreeze($persistent = null)
    {
        if (null !== $persistent) {
            foreach ($this as $child) {
                $child->persistentFreeze($persistent);
            }
        }
        return parent::persistentFreeze($persistent);
    }

   /**
    * Appends an element to the container
    *
    * If the element was previously added to the container or to another
    * container, it is first removed there.
    *
    * @param    HTML_QuickForm2_Node     Element to add
    * @return   HTML_QuickForm2_Node     Added element
    * @throws   HTML_QuickForm2_InvalidArgumentException
    */
    public function appendChild(HTML_QuickForm2_Node $element)
    {
        if ($this === $element->getContainer()) {
            $this->removeChild($element);
        }
        $this->renameChild($element);
        $element->setContainer($this);
        $this->elements[] = $element;
        return $element;
    }

   /**
    * Performs the server-side validation
    *
    * This method also calls validate() on all contained elements.
    *
    * @return   boolean Whether the container and all contained elements are valid
    */
    protected function validate()
    {
        $valid = parent::validate();
        foreach ($this as $child) {
            $valid = $child->validate() && $valid;
        }
        return $valid;
    }


   /**
    * Renders the group using an HTML_QuickForm2_Renderer
    *
    * @param    HTML_QuickForm2_Renderer   QuickForm2 renderer
    * @return   string                     HTML output
    * @throws   HTML_QuickForm2_NotFoundException if the renderer is provided
    *               but no render callback was defined in the renderer
    */
    public function render(HTML_QuickForm2_Renderer $renderer)
    {
        $html = array();
        foreach ($this as $element) {
            $html[] = $renderer->render($element);
        }
        return implode(self::getOption('linebreak'), $html);
    }


   /**
    * Default rendering
    *
    * @return   string  HTML output
    */
    public function __toString()
    {
        $html = array();
        foreach ($this as $element) {
            $html[] = $element;
        }
        return implode(self::getOption('linebreak'), $html);
    }
}

?>