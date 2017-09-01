<?php defined("IN_GOMA") OR die();

/**
 * This is the CSS-Minifier.
 *
 * @package Goma\System\Core
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version 1.0.5
 */
class CSSMin extends gObject
{
		/**
		  * the before char.
		  *
		  * @var string
		*/
		protected $a = "";
		
		/**
		  * the current char.
		  *
		  * @var string
		*/
		protected $b = "";
		
		/**
		 * the next char.
		*/
		protected $c = "";
		
		/**
		  * the data to minify.
		  *
		  *@var string
		*/
		protected $input = "";
		
		/**
		  * the length of the data.
		  *
		  *@var int
		*/
		protected $inputLenght = 0;
		
		/**
		  * the current position.
		  *
		  *@var int
		*/
		protected $inputIndex = 0;
		
		/**
		  * the minfied version.
		  *
		  *@var string
		*/
		public $output = "";
		
		/**
		 * this array contains the data for the obfuscator.
		 *
		 *@var string
		*/
		public static $dataarray1 = array(
				" :", " {", " }", " ;", " '", " \"", " ,", "  ", ";;"
		);
		
		/**
		 * this array contains the data for the obfuscator.
		 *@var string
		*/
		public static $dataarray2 = array(
				": ", "{ ", "} ", "; ", "' ", "\" ", ", ", "  "
		);
		
		/**
		 * minfies css-code
		 *@param string $css
		 *@return string new code
		*/
		public static function minify($css)
		{
			$cssmin = new cssmin($css);
			$cssmin->min();
			return $cssmin->output;
		}
		
		/**
		 *@param string $input
		 *@param boolean pase as less-file
		*/
		public function __construct($input)
		{
			parent::__construct();

			$this->input = $input;
			$this->input = str_replace(array("\r\n", "\r", "\n", "	"), " ", $this->input);
			$this->input = preg_replace("/\/\*(.*)\*\//Usi", "", $this->input); // comments
			$this->inputLenght = strlen($this->input);
		}
		
		/**
		 * minfied the css-code
		 *@name min
		 *@access public
		 *@return string - minfied version
		*/
		public function min()
		{
				
				if(PROFILE) Profiler::mark("cssmin::min");
				$this->input = str_replace("\t", " ", $this->input);
				while($this->inputIndex < $this->inputLenght)
				{
						$this->a = isset($this->input{$this->inputIndex - 1}) ? $this->input{$this->inputIndex - 1} : null;
						$this->b = $this->input{$this->inputIndex};
						$this->c = isset($this->input{$this->inputIndex + 1}) ? $this->input{$this->inputIndex + 1} : null;
						
						if(!in_array($this->b . $this->c, self::$dataarray1) && !in_array($this->a . $this->b, self::$dataarray2))
						{
								$this->output .= $this->b;
						}
						$this->inputIndex++;
				}
				if(PROFILE) Profiler::unmark("cssmin::min");
				
				
				$this->output = str_replace(";}", "}", $this->output);
				$this->output = str_replace(" 0px", " 0", $this->output);
				
				return $this->output;
		}
}
