<?php
 
require_once('files/cache/updater.php');
//require_once('importer/lib/3rdparty/aws/aws-autoloader.php');
use Aws\S3\S3Client;

// See
// http://docs.aws.amazon.com/aws-sdk-php/guide/latest/service-s3.html
// http://docs.aws.amazon.com/aws-sdk-php/guide/latest/quick-start.html
// https://github.com/tpyo/amazon-s3-php-class
// http://aws.amazon.com/datasets/41740, s3://aws-publicdatasets/common-crawl/crawl-002/
// http://aws.amazon.com/datasets/4383,  http://s3.amazonaws.com/1000genomes 

// s3://aws-publicdatasets/common-crawl/crawl-002/2010/01/06/0/
// s3://aws-publicdatasets/common-crawl/wikipedia/dbpedia/3.8
// s3://aws-publicdatasets/common-crawl/crawl-data/CC-MAIN-2013-20/segments/1368696381249

class OC_importerS3 {

	public $bucketName;
	public $pb;
	private $client;
	private $batch;
	
	function __construct($b = false, $filesDir = null) {
		$this->batch = $b;
		if(!$this->batch){
			require_once('importerPB.class.php');
			$this->pb = new OC_importerPB();
		}
			if(empty($filesDir)){
			$this->filesDir = '/'.\OCP\USER::getUser().'/files';
		}
		else{
			$this->filesDir = $filesDir;
		}
	}

	function __destruct() {
  }
	
	/**
	 * Connect to S3 Server
	 * @param $key AWS key ID
	 * @param $secret AWS secret key
	 * @return Boolean
	 */
	private function connect($key, $secret){
	
		$this->client = S3Client::factory(array(
	    'key'    => $key,
	    'secret' => $secret,
		));

		if($this->client ){
			return TRUE;
		}
		else{
			return FALSE;
		}
	}
	
	/**
	 * List directory contents recursively.
	 * @param $url The URL of the directory whose content will be listed
	 * @param $user_info credentials array (see importer.class.php)
	 * @param $user_info credentials array (see importer.class.php)
	 * @return array of paths. Each line should contain a file
	 * 				 path, relative to $url and have a / at the end of directory names.
	 */
	public function lsDir($url, $user_info){
		$user = "";
		$pass = "";
		if(empty($user_info['us_password'])){
			$user_info = OC_importer::getUserProviderInfo('S3', '');
		}
		if(isset($user_info['us_username'])){
			$user = $user_info['us_username'];
			$pass = $user_info['us_password'];
		}
		$purl = parse_url($url);
		if(isset($purl['user'])){
			$user = $purl['user'];
		}
		if(isset($purl['pass'])){
			$pass = $purl['pass'];
		}
		$client = S3Client::factory(array(
				'key'    => $user,
				'secret' => $pass,
		));
		$bucket =  $purl['host'];
		$object_key = $purl['path'];
		$object_key = preg_replace('/\/$/', '', $object_key);
		$object_key = preg_replace('/^\//', '', $object_key);
		OC_Log::write('importer','Path: '.$purl['path'].'. Bucket: '.$bucket.'. Path: '.$object_key.'. key: '.$user.'. secret: '.$pass, OC_Log::WARN);
		$iterator = $client->getIterator('ListObjects', array(
					'Bucket' => $bucket,
					'Prefix'    => $object_key
		));
		$out = array();
		foreach ($iterator as $object) {
			//OC_Log::write('importer','Object: '.$object['Key'], OC_Log::WARN);
			$out[] = 's3://'.$bucket.'/'.$object['Key'];
		}
		return $out;
	}

	
	/**
	 * Get file
	 * @param $url The URL of the file to be downloaded
	 * @param $dir The local directory
	 * @param $l Lang
	 * @param $overwrite Overwrite the target file
	 * @param $preserveDir Keep remote directory structure
	 * @param $masterpw Master password for the key store
	 * @param $verbose
	 */
	public function getFile($url, $dir, $l, $overwrite = false, $preserveDir = false, $masterpw = null,
			$verbose=false){
		try{
		
			$user = "";
			$pass = "";
			
			$user_info = OC_importer::getUserProviderInfo('S3', '', $masterpw);
			if(isset($user_info['us_username'])){
				$user = $user_info['us_username'];
				$pass = $user_info['us_password'];
			}
		
			$purl = parse_url($url);

			if(isset($purl['user'])){
				$user = $purl['user'];
			}
			if(isset($purl['pass'])){
				$pass = $purl['pass'];
			}
			
			if(!$this->connect($user, $pass)){
				throw new Exception($l->t('Connection failed'));
			}
			
			$bucket = $purl['host'];
			$object_key = urldecode($purl['path']);
			OC_Log::write('importer','Getting: '.$purl['path']." from ".$bucket, OC_Log::WARN);
			$size = $this->getRemoteFileSize($bucket, $object_key);
			if($size<=0){
				throw new Exception($l->t('File size is').' '.$size);
			}
			OC_Log::write('importer','Size: '.$size, OC_Log::WARN);
			
			//$fs = OCP\Files::getStorage();
			$fs = new \OC\Files\View($this->filesDir);
			
			$dl_dir = strlen($dir)==0?OC_importer::getDownloadFolder():( $dir[0]==='/'?$dir:OC_importer::getDownloadFolder()."/".$dir);
			
			$rpathinfo = pathinfo(urldecode($purl['path']));
			$filename = $rpathinfo['basename'];

			if($preserveDir){
				$dirs = explode("/", $rpathinfo['dirname']);
				$mydir = $dl_dir;
				foreach($dirs as $dir){
					$mydir = $mydir . "/" . $dir;
					if(!$fs->file_exists($mydir)){
						OC_Log::write('importer','Creating: '.$mydir, OC_Log::WARN);
						$fs->mkdir($mydir, 0755, true);
					}
				}
				$dl_dir = $dl_dir . "/" . $rpathinfo['dirname'];
				$dl_dir = preg_replace('/\/\/+/', '/', $dl_dir);
				if(!$fs->is_dir($dl_dir)){
					throw new Exception($l->t('Could not create directory '.$dl_dir));
				}
			}

			$local_file = $dl_dir . "/" . $filename;
			if($fs->file_exists($local_file)){
				if(!$overwrite){
					$filename = md5(rand()) . '_' . $filename;
				}
				else{
					$fs->unlink($local_file);
				}
			}

			$chunkSize = self::getChunkSize($size);
			$received_file = fopen($fs->getLocalFile($local_file), 'a');			
			$received = $last = $end = 0;
			$start_time = microtime(true);
			OC_Log::write('importer','Getting: '.$object_key, OC_Log::WARN);
			while (TRUE) {
				if($received+$chunkSize>$size){
					$end = $size-1;
					//$chunkSize = $size - $received;
				}
				else{
					$end = $received + $chunkSize - 1 ;
				}
				//OC_Log::write('importer','Reading: '.$chunkSize.': '.'bytes='.$received.'-'.$end, OC_Log::WARN);
				$result = $this->client->getObject(array(
				    'Bucket' => $bucket,
				    'Key'    => $object_key,
				    'Range' => 'bytes='.$received.'-'.$end
				));
				// Seek to the beginning of the stream
				$result['Body']->rewind();
				// Read the body off of the underlying stream in chunks
				$ret = $result['Body']->read($chunkSize);
				fwrite($received_file, $ret);
				$received += strlen($ret);//$fs->filesize($local_file);
				$percent = @round(($received/$size)*100, 2);
				if(!$this->batch){
					$this->pb->setProgressBarProgress($percent);
				}
				else{
					$verbose && print($percent."%\n");
					flush();
				}
				if($received == $size){
					break;
				}
				usleep(100);
			}
			$cacheUpdater = new \OC\Files\Cache\Updater($fs);
			$cacheUpdater->update($local_file);
			$end_time = microtime(true);
			$spent_time = $end_time-$start_time;
			$mbps = $size/$spent_time/(pow(10, 6));
			OC_Log::write('importer','Done', OC_Log::WARN);
			OC_importer::setUserHistory($url, 1);
			if(!$this->batch){
				$this->pb->setProgressBarProgress(100);
			}
			else{
				$verbose && print("Done (size: ".$size." bytes, time: ".$spent_time." s, speed: ".$mbps." MB/s)\n");
			}
		}
		catch(exception $e){
			if(!$this->batch){
				$this->pb->setError($e->getMessage());
			}
			try{
				fclose($received_file);
			}
			catch(exception $ee){
			}
			throw $e;
		}
		try{
			fclose($received_file);
		}
		catch(exception $ee){
		}
	}

	private function getRemoteFileSize($bucket, $object_key){
		$result = $this->client->headObject(array(
			    'Bucket' => $bucket,
			    'Key'    => $object_key
			));
		return $result['ContentLength'];
	}
	
	/**
	 * Get the chunk size according to the total size
	 */
	private static function getChunkSize($fsize){
	    if($fsize <= 1024*1024){
	        return 4096;
	    }
	    if($fsize <= 1024*1024*10){
	        return 4096*10;
	    }
	    if($fsize <= 1024*1024*40){
	        return 4096*30;
	    }
	    if($fsize <= 1024*1024*80){
	        return 4096*47;
	    }
	    if($fsize <= 1024*1024*120){
	        return 4096*65;
	    }
	    if($fsize <= 1024*1024*150){
	        return 4096*70;
	    }
	    if($fsize <= 1024*1024*200){
	        return 4096*85;
	    }
	    if($fsize <= 1024*1024*250){
	        return 4096*100;
	    }
	    if($fsize <= 1024*1024*300){
	        return 4096*115;
	    }
	    if($fsize <= 1024*1024*400){
	        return 4096*135;
	    }
	    if($fsize <= 1024*1024*500){
	        return 4096*170;
	    }
	    if($fsize <= 1024*1024*1000){
	        return 4096*200;
	    }
	    return 4096*210;
	}
	
}
