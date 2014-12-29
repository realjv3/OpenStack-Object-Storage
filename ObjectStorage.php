<?php
/*
Use cUrl to get API token and a URL that contains to full path to object storage account. 
cUrl options set: url to connect to, custom http headers to send (username and password), set cUrl to accept any SSL server (https probz), set cUrl to output http header, set cUrl to output to string instead of just stdout;
*/
$curl = curl_init();

curl_setopt($curl, CURLOPT_URL, "SET ME");
curl_setopt($curl, CURLOPT_HTTPHEADER, array("X-Auth-User: SET ME", "X-Auth-Key: SET ME"));
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_HEADER, 1);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

$curl_result = curl_exec($curl);

curl_close($curl);

/*
Get the API token and url from the returned http header and store them in vars to use in future operations
*/

$x_auth_token = substr($curl_result, 67, 53);
$x_storage_url = substr($curl_result, 180, 101);

/*
Download a file from object storage
*/

$curl = curl_init();

curl_setopt($curl, CURLOPT_URL, "https://dal05.objectstorage.softlayer.net/auth/v1.0");
curl_setopt($curl, CURLOPT_HTTPHEADER, array("x_auth_token", "x_storage_url/test1/file1.txt"));
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_HEADER, 1);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

curl_setopt($curl, CURLOPT_FILE, fopen("file1.txt", "w"));

curl_exec($curl);

curl_close($curl);

?>