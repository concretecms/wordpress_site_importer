<?php  
defined('C5_EXECUTE') or die(_("Access Denied."));
//this is just a cobbling together of the file_importer and the file_incoming
class WordpressFileImporter {

	function importFile($fileUrl){
	$u = new User();
	
	$cf = Loader::helper('file');
	$fp = FilePermissions::getGlobal();
	if (!$fp->canAddFiles()) {
		die(t("Unable to add files."));
	}
	
	//$valt = Loader::helper('validation/token');
	Loader::library("file/importer");
	Loader::library('3rdparty/Zend/Http/Client');
	Loader::library('3rdparty/Zend/Uri/Http');
	$file = Loader::helper('file');
	Loader::helper('mime');
	
	$error = array();
	
	// load all the incoming fields into an array
	$this_url = $fileUrl;
	
		// validate URL
		if (Zend_Uri_Http::check($this_url)) {
			// URL appears to be good... add it
			$incoming_urls[] = $this_url;
		} else {
			$errors[] = '"' . $this_url . '"' . t(' is not a valid URL.');
		}
	//}
	
	//if (!$valt->validate('import_remote')) {
	//	$errors[] = $valt->getErrorMessage();
	//}
	
	
	if (count($incoming_urls) < 1) {
		$errors[] = t('You must specify at least one valid URL.');
	}
	
	$import_responses = array();
	
	// if we haven't gotten any errors yet then try to process the form
	if (count($errors) < 1) {
		// itterate over each incoming URL adding if relevant
		foreach($incoming_urls as $this_url) {
			// try to D/L the provided file
// This all sets up the CURL actions to check the page
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $this_url);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 10); //follow up to 10 redirections - avoids loops
$data = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get the HTTP Code
// Get final redirected URL, will be the same if URL is not redirected
$new_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); 
curl_close($ch);

// Array of HTTP status codes. Trim down if you would like to.
$codes = array(0=>'Domain Not Found',
			   100=>'Continue',
			   101=>'Switching Protocols',
			   200=>'OK',
			   201=>'Created',
			   202=>'Accepted',
			   203=>'Non-Authoritative Information',
			   204=>'No Content',
			   205=>'Reset Content',
			   206=>'Partial Content',
			   300=>'Multiple Choices',
			   301=>'Moved Permanently',
			   302=>'Found',
			   303=>'See Other',
			   304=>'Not Modified',
			   305=>'Use Proxy',
			   307=>'Temporary Redirect',
			   400=>'Bad Request',
			   401=>'Unauthorized',
			   402=>'Payment Required',
			   403=>'Forbidden',
			   404=>'Not Found',
			   405=>'Method Not Allowed',
			   406=>'Not Acceptable',
			   407=>'Proxy Authentication Required',
			   408=>'Request Timeout',
			   409=>'Conflict',
			   410=>'Gone',
			   411=>'Length Required',
			   412=>'Precondition Failed',
			   413=>'Request Entity Too Large',
			   414=>'Request-URI Too Long',
			   415=>'Unsupported Media Type',
			   416=>'Requested Range Not Satisfiable',
			   417=>'Expectation Failed',
			   500=>'Internal Server Error',
			   501=>'Not Implemented',
			   502=>'Bad Gateway',
			   503=>'Service Unavailable',
			   504=>'Gateway Timeout',
			   505=>'HTTP Version Not Supported');
			if (isset($codes[$http_code])) {
				if ($codes[$http_code] == "OK"){
					$client = new Zend_Http_Client($this_url);
			
			
			$response = $client->request();
			if ($response->isSuccessful()) {
				$uri = Zend_Uri_Http::fromString($this_url);
				$fname = '';
				$fpath = $file->getTemporaryDirectory();
	
				// figure out a filename based on filename, mimetype, ???
				if (preg_match('/^.+?[\\/]([-\w%]+\.[-\w%]+)$/', $uri->getPath(), $matches)) {
					// got a filename (with extension)... use it
					$fname = $matches[1];
				} else if (! is_null($response->getHeader('Content-Type'))) {
					// use mimetype from http response
					$fextension = MimeHelper::mimeToExtension($response->getHeader('Content-Type'));
					if ($fextension === false)
						$errors[] = t('Unknown mime-type: ') . $response->getHeader('Content-Type');
					else {
						// make sure we're coming up with a unique filename
						do {
							// make up a filename based on the current date/time, a random int, and the extension from the mime-type
							$fname = date('d-m-Y_H:i_') . mt_rand(100, 999) . '.' . $fextension;
						} while (file_exists($fpath.'/'.$fname));
					}
				} //else {
					// if we can't get the filename from the file itself OR from the mime-type I'm not sure there's much else we can do
				//}
	
				if (strlen($fname)) {
					// write the downloaded file to a temporary location on disk
					$handle = fopen($fpath.'/'.$fname, "w");
					fwrite($handle, $response->getBody());
					fclose($handle);
	
					// import the file into concrete
					if ($fp->canAddFileType($cf->getExtension($fname))) {
						$fi = new FileImporter();
						$resp = $fi->import($fpath.'/'.$fname, $fname, $fr);
					} else {
						$resp = FileImporter::E_FILE_INVALID_EXTENSION;
					}
					if (!($resp instanceof FileVersion)) {
						$errors[] .= $fname . ': ' . FileImporter::getErrorMessage($resp) . "\n";
					} else {
						$import_responses[] = $resp;
					}
	
					// clean up the file
					unlink($fpath.'/'.$fname);
				} else {
					// could not figure out a file name
					$errors[] = t('Could not determine the name of the file at ') . $this_url;
				}
			} else {
				// warn that we couldn't download the file
				$errors[] = t('There was an error downloading ') . $this_url;
			}
				}
			} else {
				$errors[] = t("Error connecting to file's server, file skipped");
			}
			
			
		}
	}
	//print_r($errors);
	if($resp instanceof FileVersion){
		return $resp;
	}
	
	}
} //end class
?>
