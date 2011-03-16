<?

$filetypes = array(
	".html" => "text/html",
	".css"  => "text/css",
	".js"   => "text/javascript",
	".png"  => "image/png",
	".jpeg" => "image/jpeg",
	".jpg"  => "image/jpeg",
	".gif"  => "image/gif",
);

function staticcontent($data, $user, $url){
	return servestatic($data, $user, $url, "/static/");
}

function staticimages($data, $user, $url){
	return servestatic($data, $user, $url, "/images/");
}

function servestatic($data, $user, $url, $base){
	global $filetypes;

	if(substr($url, 0, strlen($base)) != $base)
		redirect("/404?referer=$url");

	$url = str_replace("../", "", $url);
	$url = str_replace("./", "", $url);

	$file = substr($url, 1);

	if(!file_exists($file))
		redirect("/404?referer=$url");


	$mtime = filemtime($file);

	if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])){
		$iftime = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
		if($mtime <= $iftime){
			header($_SERVER["SERVER_PROTOCOL"] . " 304 Not Modified");
			return false;
		}
	}

	$fileending = substr($file, strrpos($file,'.'));
	header("content-type: " . $filetypes[$fileending]);
	header("expires: " . date(DATE_RFC822, time()+86400*7));
	header("last-modified: " . date(DATE_RFC822, $mtime));
	header("cache-control: public, max-age=31536000");

	readfile($file);

	return false;
}

