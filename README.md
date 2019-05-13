# s3-multi-part-upload-js
Javascript code to upload files from browser to aws s3

This code is taken from https://github.com/zdresearch/s3-multipart-upload-javascript-browser and modified to support custom number of max simultaneous upload requests,

The code in above link will start uploading all parts at once

so for example if max_part_size is set to 10MB and you upload a 10GB file it will generate 1000 simultaneous connections, 

This code is modified to allow user to configure max simultaneous upload requests easily using max_parallel_xhrs variable.



Basic Usage:

var upfile = new S3MultiUpload(document.querySelector("#file_field").files[0]);
upfile.start();
upfile.onUploadCompleted = function(s3_url){
//do something with the file url
}
