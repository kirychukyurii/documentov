ERROR

	ACTION: tool/mail/send	Отправка почты на xxx@gmail.com
	<b>Warning</b>: fsockopen(): SSL operation failed with code 1. OpenSSL Error messages:
	error:14090086:SSL routines:ssl3_get_server_certificate:certificate verify failed

DECISION

	php.ini:
		openssl.cafile=path_to_Documentov/misc/cacert.pem