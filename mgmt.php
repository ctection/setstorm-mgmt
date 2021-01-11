<?PHP
header("Access-Control-Allow-Origin: *");
// CONFIGURATION

$secret = "REPLACE THIS WITH YOUR SECRET";
$whitelist_enabled = false;
$whitelist_users = array(1,2,3);
$superuser = 1;

$json_output_obj = array();
if(isset($_GET["secret"])) {
	if(!is_dir("./tempuploads/")) {
		mkdir("./tempuploads/");
		file_put_contents("./tempuploads/.htaccess","Require all denied");
	}
		
	if(!is_dir("./v/")) {
		mkdir("./v/");
		file_put_contents("./v/.htaccess","Require all denied");
	}
	if($_GET["secret"] == $secret) {
		if(isset($_GET["action"])) {
			if($_GET["action"] == "ping"){
				$json_output_obj["error"] = false;
				$json_output_obj["message"] = "Pong";
			}
			if($_GET["action"] == "speedtest") {
				$data = str_repeat(rand(0,9), 1500000);
				echo $data;
			}
			if($_GET["action"] == "finalize_upload") {
				if(isset($_GET["rid"]) && isset($_GET["vid"]) && isset($_GET["fileext"])){
					
					$rid = filter_var($_GET["rid"],FILTER_SANITIZE_STRING);
					$vid = filter_var($_GET["vid"],FILTER_SANITIZE_NUMBER_INT);
					$ext = filter_var($_GET["fileext"],FILTER_SANITIZE_STRING);
					
					if(file_exists("./tempuploads/".$rid.".".$ext)){
						rename("./tempuploads/".$rid.".".$ext,"./tempuploads/".$rid.".published.".$ext);
						execInBackground("ffmpeg -t 10 -i ./tempuploads/".$rid.".published.".$ext." -vf \"fps=7,scale=380:-1\" -loop 0 ./v/".$vid.".preview.gif");
						execInBackground("ffmpeg -i ./tempuploads/".$rid.".published.".$ext." -c:v libx264 -b:v 5M -maxrate 6M -preset medium -vf scale=1920:-2 -r 50 -c:a aac -b:a 256K ./v/".$vid.".1080.mp4");
						execInBackground("ffmpeg -i ./tempuploads/".$rid.".published.".$ext." -c:v libx264 -b:v 2M -maxrate 3.5M -preset fast -vf scale=1280:-2 -r 50 -c:a aac -b:a 192K ./v/".$vid.".720.mp4");
						execInBackground("ffmpeg -i ./tempuploads/".$rid.".published.".$ext." -c:v libx264 -b:v 1M -maxrate 1.5M -preset fast -vf scale=640:-2 -r 50 -c:a aac -b:a 128K ./v/".$vid.".360.mp4");
						$json_output_obj["error"] = false;
						$json_output_obj["status"] = "Processing";
					}else{
						$json_output_obj["error"] = true;
						$json_output_obj["error_desc"] = "Source file missing";
					}
				}
			}
			if($_GET["action"] == "register_upload_token"){
				if(isset($_GET["uid"])){
					$uid = filter_var($_GET["uid"],FILTER_SANITIZE_NUMBER_INT);
					if($whitelist_enabled){
						if(!in_array($uid,$whitelist_users)){
							$json_output_obj["error"] = true;
							$json_output_obj["error_desc"] = "You are not whitelisted";
							die(json_encode($json_output_obj));
						}
					}
					$upload_token = generateRandomString(32);
					setDBValue($upload_token,$uid);
					setDBValue($upload_token.".validity",time()+43200);
					$json_output_obj["error"] = false;
					$json_output_obj["token"] = $upload_token;
				}
			}
			if($_GET["action"] == "diskusage"){
				$json_output_obj["error"] = false;
				$json_output_obj["total"] = disk_total_space(__DIR__);
				$json_output_obj["free"] = disk_free_space(__DIR__);
			}
		}else{
			$json_output_obj["error"] = true;
			$json_output_obj["error_desc"] = "Action missing";
		}
	}else{
		$json_output_obj["error"] = true;
		$json_output_obj["error_desc"] = "Secret incorrect";
	}
}else{
	$json_output_obj["error"] = true;
	$json_output_obj["error_desc"] = "Secret missing";
}
if(isset($_POST["start"]) && isset($_POST["end"]) && isset($_POST["fileext"]) && isset($_POST["rid"]) && isset($_FILES["file"])){
	if(isset($_POST["upload_token"])){
		// Process file upload
		
		$upload_token = filter_var($_POST["upload_token"],FILTER_SANITIZE_STRING);
		$uid = getDBValue($upload_token);
		if($uid == null){
			$json_output_obj["error"] = true;
			$json_output_obj["error_desc"] = "Invalid Upload Token";
			die(json_encode($json_output_obj));
		}else{
			$validity = getDBValue($upload_token.".validity");
			if($validity != null && $validity >= time()){
				// Token Valid
			}else{
				$json_output_obj["error"] = true;
				$json_output_obj["error_desc"] = "Invalid Upload Token";
				die(json_encode($json_output_obj));
			}
		}
		if($whitelist_enabled){
			if(in_array($uid,$whitelist_users)){
				//Everything is fine
			}else{
				$json_output_obj["error"] = true;
				$json_output_obj["error_desc"] = "You are not whitelisted";
				die(json_encode($json_output_obj));
			}
		}
		
		
		
		$rid = filter_var($_POST["rid"],FILTER_SANITIZE_STRING);
		$ext = filter_var($_POST["fileext"],FILTER_SANITIZE_STRING);
		
		$chunk = file_get_contents($_FILES["file"]['tmp_name']);
		file_put_contents("./tempuploads/".$rid.".".$ext,$chunk,FILE_APPEND);
		
		$json_output_obj["error"] = false;
		$json_output_obj["error_desc"] = "";
		die(json_encode($json_output_obj));
	}
}


echo json_encode($json_output_obj);

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function execInBackground($cmd) {
    if (substr(php_uname(), 0, 7) == "Windows"){
        pclose(popen("start /B ". $cmd, "r")); 
    }
    else {
        exec($cmd . " > /dev/null &");  
    }
} 

function setDBValue($key,$value){
	if(!is_dir("./data/")){
		mkdir("./data/");
		file_put_contents("./data/.htaccess","Require all denied");
	}
	file_put_contents("./data/".$key,$value);
}

function getDBValue($key){
	if(file_exists("./data/".$key)){
		return file_get_contents("./data/".$key);
	}else{
		return null;
	}
}


?>
