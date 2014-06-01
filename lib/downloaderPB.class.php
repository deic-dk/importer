<?php

/**
* ownCloud downloader app
*
* @author Xavier Beurois
* @copyright 2012 Xavier Beurois www.djazz-lab.net
* 
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*  
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
* Create with http://spidgorny.blogspot.com/2012/02/progress-bar-for-lengthy-php-process.html
* 
*/

/**
 * This class manages downloader progress bar. 
 */
class OC_downloaderPB {
	
	private  $percentDone = 0;
	private  $pbid;
	private  $pbarid;
	private  $tbarid;
	private  $textid;
	private  static $decimals = 1;
	
	function __construct() {
		$this->pbid = md5(rand());
		$this->pbarid = $this->pbid . "_pbar";
		$this->tbarid = $this->pbid . "_tbar";
		$this->textid = $this->pbid . "_text";
  }
   
	function __destruct() {
  }

	/**
	 * Render the initial progress bar
	 */
	public function render(){
		print($this->getContent());
		self::flush();
	}

	/**
	 * Set progress bar percent progress
	 * @param $percentDone The progress bar value
	 */
	public function setProgressBarProgress($percentDone){
		$this->percentDone = $percentDone;
		$text = number_format($percentDone, self::$decimals, '.', '').'%';
		print('<script type="text/javascript">if(document.getElementById("'.$this->pbarid.'")){document.getElementById("'.$this->pbarid.'").style.width = "'.$percentDone.'%";');
		if($percentDone!=100){
			print('document.getElementById("'.$this->tbarid.'").style.width = "'.(100-$percentDone).'%";document.getElementById("'.$this->textid.'").innerHTML = "'.htmlspecialchars($text).'";');
		}
		else{
			print('document.getElementById("'.$this->textid.'").innerHTML = "'.htmlspecialchars($text).'";');
		}
		print('}</script>'."\n");
		self::flush();
	}
	
	/**
	 * Set text message on the progress bar div.
	 * @param $text The text to display
	 */
	public function setText($text){
		print('<script type="text/javascript">document.getElementById("'.$this->textid.'").innerHTML = "'.htmlspecialchars($text).'";</script>'."\n");
		self::flush();
	}
	
	/**
	 * Set error message on the progress bar div.
	 * @param $error The error to display
	 */
	public function setError($error){
		print('<script type="text/javascript">document.getElementById("'.$this->pbid.'").removeChild(document.getElementById("pb_bar"));document.getElementById("'.$this->textid.'").innerHTML = "<img src=\"/apps/downloader/img/warning.png\" style=\"vertical-align:middle;margin-right:5px;\" />'.htmlspecialchars($error).'";</script>'."\n");
		self::flush();
	}

		/**
	 * Set error message on the progress bar div of the directory URL pane.
	 * @param $error The error to display
	 */
	public static function setDirError($error){
		print('<script type="text/javascript">$("#folder_pop .elts span.dling").html("'.htmlspecialchars($error).'");</script>\n');
		self::flush();
	}

	
	/**
	 * Flush (Call this function each time you need to display something).
	 */
	public static function flush() {
		print str_pad('', intval(ini_get('output_buffering')))."\n";
		flush();
	}
	
	/**
	 * Get initial content
	 */
	private function getContent(){
		$this->percentDone = floatval($this->percentDone);
		$percentDone = number_format($this->percentDone, self::$decimals, '.', '') .'%';
		$content = '<div id="'.$this->pbid.'" class="pb_container"><div id="'.$this->textid.'" class="pb_text">'.$this->percentDone.'</div><div id="pb_bar" class="pb_bar"><div id="'.$this->pbarid.'" class="pb_before" style="width: '.$this->percentDone.';"></div><div id="'.$this->tbarid.'" class="pb_after"></div></div><br style="height: 1px; font-size: 1px;"/></div>';
		$content .= '<style>.pb_container{position:relative;}.pb_bar{width:100%;height:1em;border:1px solid silver;-moz-border-radius-topleft:5px;-moz-border-radius-topright:5px;-moz-border-radius-bottomleft:5px;-moz-border-radius-bottomright:5px;-webkit-border-top-left-radius:5px;-webkit-border-top-right-radius:5px;-webkit-border-bottom-left-radius:5px;-webkit-border-bottom-right-radius:5px;}.pb_before{float:left;height:1em;background-color:#1D2D44;-moz-border-radius-topleft:5px;-moz-border-radius-bottomleft:5px;-moz-border-radius-topright:5px;-moz-border-radius-bottomright:5px;-webkit-border-top-left-radius:5px;-webkit-border-bottom-left-radius:5px;-webkit-border-top-right-radius:5px;-webkit-border-bottom-right-radius:5px;}.pb_after{float:left;height:1em;background-color:#FEFEFE;-moz-border-radius-topright:5px;-moz-border-radius-bottomright:5px;-webkit-border-top-right-radius:5px;-webkit-border-bottom-right-radius:5px;}.pb_text{text-align:left;}</style>'."\r\n";
		return $content;
	}
	
}	