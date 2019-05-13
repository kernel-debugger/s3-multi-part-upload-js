<?php

require 'aws/aws-autoloader.php';

use Aws\Exception\AwsException;


function aws_key(){
        return 'aws_access_key';
}
    function aws_secret(){
        return 'aws_secret';
}
    function bucket() {
        return "bucket_name";
}
/**
 * The key perfix in the bucket to put all uploads in
 * @return string
 */
function prefix() {
    $file_number = file_get_contents("uploads_counter");
    $file_number = (int)$file_number + 1;
    file_put_contents("uploads_counter",$file_number);
    return 'messages/attachments/'.$file_number."-";
}
/**
 * Easy wrapper around S3 API
 * @param  string $command the function to call
 * @param  mixed $args    variable args to pass
 * @return mixed
 */
function s3($command=null,$args=null)
{
	static $s3=null;
	if ($s3===null)
	$s3 = new Aws\S3\S3Client([
	    'version' => 'latest',
	    'region'  => 'us-east-1',
	    'signature_version' => 'v4',
	        'credentials' => [
	        'key'    => aws_key(),
	        'secret' => aws_secret(),
	    ]
	]);
	if ($command===null)
		return $s3;
	$args=func_get_args();
	array_shift($args);
	try {
		$res=call_user_func_array([$s3,$command],$args);
		return $res;
	}
	catch (AwsException $e)
	{
		echo $e->getMessage(),PHP_EOL;
	}	
	return null;
}
/**
 * Output data as json with proper header
 * @param  mixed $data
 */
function json_output($data)
{
    header('Content-Type: application/json');
    die(json_encode($data));
}

if (isset($_POST['command']))
{
	$command=$_POST['command'];
	if ($command=="create")
	{
		$res=s3("createMultipartUpload",[
		    'ACL' => 'public-read',
			'Bucket' => bucket(),
            'Key' => prefix().$_POST['fileInfo']['name'],
            'ContentType' => $_REQUEST['fileInfo']['type'],
            'Metadata' => $_REQUEST['fileInfo'],
            'StorageClass' => "ONEZONE_IA"
		]);
	 	json_output(array(
               'uploadId' => $res->get('UploadId'),
                'key' => $res->get('Key'),
        ));
	}
	if ($command=="part")
	{
		$command=s3("getCommand","UploadPart",[
			'Bucket' => bucket(),
            'Key' => $_REQUEST['sendBackData']['key'],
            'UploadId' => $_REQUEST['sendBackData']['uploadId'],
            'PartNumber' => $_REQUEST['partNumber'],
            'ContentLength' => $_REQUEST['contentLength']
		]);
        // Give it at least 24 hours for large uploads
		$request=s3("createPresignedRequest",$command,"+5 hours");
        json_output([
            'url' => (string)$request->getUri(),
        ]);		
	}
	if ($command=="complete")
	{
	 	$partsModel = s3("listParts",[
            'Bucket' => bucket(),
            'Key' => $_REQUEST['sendBackData']['key'],
            'UploadId' => $_REQUEST['sendBackData']['uploadId'],
        ]); 
        $model = s3("completeMultipartUpload",[
            'Bucket' => bucket(),
            'Key' => $_REQUEST['sendBackData']['key'],
            'UploadId' => $_REQUEST['sendBackData']['uploadId'],
            'MultipartUpload' => [
            	"Parts"=>$partsModel["Parts"],
            ]
        ]);
        json_output([
            'success' => true,
            'location' => $model["Location"]
        ]);
	}
	if ($command=="abort")
	{
		 $model = s3("abortMultipartUpload",[
            'Bucket' => bucket(),
            'Key' => $_REQUEST['sendBackData']['key'],
            'UploadId' => $_REQUEST['sendBackData']['uploadId']
        ]);
        json_output([
            'success' => true
        ]);
	}
	exit(0);
}
