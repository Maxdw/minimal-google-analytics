<?php
/**
 * HtmlHelper
 *
 * Simple helper class that allows the user to concatenate
 * a fragment of HTML based on the required elements.
 *
 * Use $instance->open to open an element
 * Use $instance->close to close # of (or all) elements
 * Use $instance->single to append a self closing element
 * Use $instance->element to append an element
 * Use $instance->append to append raw content to the HTML
 *
 * Typecasting $instance to (string) or calling $instance->render()
 * will return all previously declared HTML if $instance was created
 * using $capture = true (default).
 *
 * @copyright Copyright (c) Dutchwise V.O.F. (http://dutchwise.nl)
 * @author Max van Holten (<max@dutchwise.nl>)
 * @license http://www.opensource.org/licenses/mit-license.php MIT license
 */
if(!class_exists('HtmlHelper')) {
	class HtmlHelper {
		
		/**
		 * Stores tagNames that were opened.
		 *
		 * @var array
		 */
		protected $_openedTags = array();
		
		/**
		 * Indicates whether to capture strings.
		 *
		 * @var boolean
		 */
		protected $_captureEnabled = false;
		
		/**
		 * Stores captured strings.
		 *
		 * @var string
		 */
		protected $_output = '';
		
		/**
		 * Renders the provided attributes.
		 * - Attributes with values set to NULL are skipped.
		 * - Attributes with numeric keys are added by value as valueless attributes
		 *
		 * @param array $attr
		 * @return string
		 */
		protected function _renderAttributes(array $attr = array()) {
			$lines = array();
			
			foreach($attr as $name => $value) {
				if(is_null($value)) {
					continue;
				}
				
				if(is_numeric($name)) {
					$lines[] = $value;
				}
				else {
					$lines[] = "{$name}=\"{$value}\"";
				}
			}
			
			$lines = ' ' . implode(' ', $lines);
			
			if(!$attr) {
				$lines = trim($lines); 
			}
			
			return $lines;
		}
		
		/**
		 * Class constructor
		 *
		 * @param boolean $capture Enables capturing strings.
		 */
		public function __construct($capture = true) {
			$this->_captureEnabled = $capture;
		}
		
		/**
		 * Opens an element, allowing other code to
		 * render the contents of the element.
		 *
		 * @param string $tagName
		 * @param array $attr
		 * @return string
		 */
		public function open($tagName, array $attr = array()) {
			$attr = $this->_renderAttributes($attr);
			$this->_openedTags[] = $tagName;
			$result = "<{$tagName}{$attr}>";
			
			if($this->_captureEnabled) {
				$this->_output .= $result;
			}
			
			return $result;
		}
		
		/**
		 * Renders a single self closing element.
		 *
		 * @param string $tagName
		 * @param array $attr
		 * @return string
		 */
		public function single($tagName, array $attr = array()) {
			$attr = $this->_renderAttributes($attr);
			$result = "<{$tagName}{$attr} />";
			
			if($this->_captureEnabled) {
				$this->_output .= $result;
			}
			
			return $result;
		}
		
		/**
		 * Closes elements opened with the self::open
		 * method and returns the closing tags.
		 *
		 * @param int $amount
		 * @return string
		 */
		public function close($amount = INF) {
			$open =& $this->_openedTags;
			$str = '';
			
			while($amount-- && $open) {
				$str .= '</' . array_pop($open) . '>';
			}
			
			if($this->_captureEnabled) {
				$this->_output .= $str;
			}
			
			return $str;
		}
		
		/**
		 * Appends the provided HTML to the
		 * output string.
		 *
		 * @param string $html
		 * @return void
		 */
		public function append($html) {
			$this->_output .= $html;
		}
		
		/**
		 * Renders a element of the provided
		 * tagName, attributes and content.
		 *
		 * @return string
		 */
		public function element($tagName, $attr = array(), $content = '') {
			if(is_string($attr)) {
				$content = $attr;
				$attr = array();
			}
			
			$attr = $this->_renderAttributes($attr);
			$result = "<{$tagName}{$attr}>{$content}</{$tagName}>";
			
			if($this->_captureEnabled) {
				$this->_output .= $result;
			}
			
			return $result;		
		}
		
		/**
		 * Renders previously captured output.
		 *
		 * @return string
		 */
		public function render() {
			$output = $this->_output;
			$this->_output = '';
			return $output;
		}
		
		/**
		 * When this instance gets typecasted to string
		 * the output will be rendered.
		 *
		 * @magic __toString
		 * @return string
		 */
		public function __toString() {
			return $this->render();
		}
		 
	}
}
?>