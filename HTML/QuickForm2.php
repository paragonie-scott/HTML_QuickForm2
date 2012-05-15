<?php
/**
 * Class representing a HTML form
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2006-2012, Alexey Borzov <avb@php.net>,
 *                          Bertrand Mansion <golgote@mamasam.com>
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
 * @category HTML
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  SVN: $Id$
 * @link     http://pear.php.net/package/HTML_QuickForm2
 */

/**
 * Abstract base class for QuickForm2 containers
 */
require_once 'HTML/QuickForm2/Container.php';

/**
 * Data source for HTML_QuickForm2 objects based on superglobal arrays
 */
require_once 'HTML/QuickForm2/DataSource/SuperGlobal.php';

/**
 * Class representing a HTML form
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/HTML_QuickForm2
 */
class HTML_QuickForm2 extends HTML_QuickForm2_Container
{
   /**
    * Data sources providing values for form elements
    * @var array
    */
    protected $datasources = array();

   /**
    * We do not allow setting "method" and "id" other than through constructor
    * @var array
    */
    protected $watchedAttributes = array('id', 'method');

   /**
    * Class constructor, form's "id" and "method" attributes can only be set here
    *
    * @param string       $id          "id" attribute of <form> tag
    * @param string       $method      HTTP method used to submit the form
    * @param string|array $attributes  Additional HTML attributes
    *                                  (either a string or an array)
    * @param bool         $trackSubmit Whether to track if the form was submitted
    *                                  by adding a special hidden field
    */
    public function __construct(
        $id, $method = 'post', $attributes = null, $trackSubmit = true
    ) {
        $method      = ('GET' == strtoupper($method))? 'get': 'post';
        $trackSubmit = empty($id) ? false : $trackSubmit;
        $this->attributes = array_merge(
            self::prepareAttributes($attributes),
            array('method' => $method)
        );
        parent::setId(empty($id) ? null : $id);
        if (!isset($this->attributes['action'])) {
            $this->attributes['action'] = $_SERVER['PHP_SELF'];
        }
        if ($trackSubmit && isset($_REQUEST['_qf__' . $id]) ||
            !$trackSubmit && ('get' == $method && !empty($_GET) ||
                              'post' == $method && (!empty($_POST) || !empty($_FILES)))
        ) {
            $this->addDataSource(new HTML_QuickForm2_DataSource_SuperGlobal(
                $method, get_magic_quotes_gpc()
            ));
        }
        if ($trackSubmit) {
            $this->appendChild(HTML_QuickForm2_Factory::createElement(
                'hidden', '_qf__' . $id, array('id' => 'qf:' . $id)
            ));
        }
        $this->addFilter(array($this, 'skipInternalFields'));
    }

    protected function onAttributeChange($name, $value = null)
    {
        throw new HTML_QuickForm2_InvalidArgumentException(
            'Attribute \'' . $name . '\' is read-only'
        );
    }

    protected function setContainer(HTML_QuickForm2_Container $container = null)
    {
        throw new HTML_QuickForm2_Exception('Form cannot be added to container');
    }

    public function setId($id = null)
    {
        throw new HTML_QuickForm2_InvalidArgumentException(
            "Attribute 'id' is read-only"
        );
    }


   /**
    * Adds a new data source to the form
    *
    * @param HTML_QuickForm2_DataSource $datasource Data source
    */
    public function addDataSource(HTML_QuickForm2_DataSource $datasource)
    {
        $this->datasources[] = $datasource;
        $this->updateValue();
    }

   /**
    * Replaces the list of form's data sources with a completely new one
    *
    * @param array $datasources A new data source list
    *
    * @throws   HTML_QuickForm2_InvalidArgumentException    if given array
    *               contains something that is not a valid data source
    */
    public function setDataSources(array $datasources)
    {
        foreach ($datasources as $ds) {
            if (!$ds instanceof HTML_QuickForm2_DataSource) {
                throw new HTML_QuickForm2_InvalidArgumentException(
                    'Array should contain only DataSource instances'
                );
            }
        }
        $this->datasources = $datasources;
        $this->updateValue();
    }

   /**
    * Returns the list of data sources attached to the form
    *
    * @return   array
    */
    public function getDataSources()
    {
        return $this->datasources;
    }

    public function getType()
    {
        return 'form';
    }

    public function setValue($value)
    {
        throw new HTML_QuickForm2_Exception('Not implemented');
    }

   /**
    * Tells whether the form was already submitted
    *
    * This is a shortcut for checking whether there is an instance of Submit
    * data source in the list of form data sources
    *
    * @return bool
    */
    public function isSubmitted()
    {
        foreach ($this->datasources as $ds) {
            if ($ds instanceof HTML_QuickForm2_DataSource_Submit) {
                return true;
            }
        }
        return false;
    }

   /**
    * Performs the server-side validation
    *
    * @return   boolean Whether all form's elements are valid
    */
    public function validate()
    {
        return $this->isSubmitted() && parent::validate();
    }

   /**
    * Renders the form using the given renderer
    *
    * @param HTML_QuickForm2_Renderer $renderer
    *
    * @return   HTML_QuickForm2_Renderer
    */
    public function render(HTML_QuickForm2_Renderer $renderer)
    {
        $renderer->startForm($this);
        $renderer->getJavascriptBuilder()->setFormId($this->getId());
        foreach ($this as $element) {
            $element->render($renderer);
        }
        $this->renderClientRules($renderer->getJavascriptBuilder());
        $renderer->finishForm($this);
        return $renderer;
    }

    /**
     * Filter for form's getValue() removing internal fields' values from the array
     *
     * @param array $value
     *
     * @return array
     * @link http://pear.php.net/bugs/bug.php?id=19403
     */
    protected function skipInternalFields($value)
    {
        foreach (array_keys($value) as $key) {
            if ('_qf' === substr($key, 0, 3)) {
                unset($value[$key]);
            }
        }
        return $value;
    }
}
?>
