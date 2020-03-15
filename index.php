<?php
function getIp() {
	if (!empty($_SERVER["HTTP_CLIENT_IP"]) and filter_var($_SERVER["HTTP_CLIENT_IP"], FILTER_VALIDATE_IP)) {
		return $_SERVER["HTTP_CLIENT_IP"];
	}
	if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"]) and filter_var($_SERVER["HTTP_X_FORWARDED_FOR"], FILTER_VALIDATE_IP)) {
		return $_SERVER["HTTP_X_FORWARDED_FOR"];
	}

	return $_SERVER["REMOTE_ADDR"];
}


function getCountryFromIp($ip) {
	$url  = sprintf("http://ip-api.com/json/%s?fields=status,countryCode", $ip);
	$json = json_decode(file_get_contents($url), true);

	return $json["status"] == "success" ? strtoupper($json["countryCode"]) : null;
}


function getCountryCode() {
	$countryCode = getCountryFromIp(getIp());

	if (in_array($countryCode, ["AE", "AU", "BR", "CA", "CN", "DE", "ES", "FR", "IN", "IT", "JP", "MX", "NL", "SG", "UK", "US"])) {
		return $countryCode;
	}
	if (in_array($countryCode, ["SE"])) {
		return "DE";
	}

	return "US";
}


function getProductId() {
	$url = substr(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), 1);
	if (!empty($url)) {
		return $url;
	}
	if (!empty($_GET["id"])) {
		return $_GET["id"];
	}

	return null;
}


function getTrackingId($countryCode) {
	$ids = json_decode(file_get_contents(__DIR__."/config.json"), true)["amazon"];

	if (!empty($ids[$countryCode])) {
		return $ids[$countryCode];
	}

	return null;
}


function generateLink($countryCode) {
	$tld        = getTld($countryCode);
	$trackingId = getTrackingId($countryCode);

	if ($trackingId === null) {
		$tld        = getTld("US");
		$trackingId = getTrackingId("US");
	}

	return sprintf("https://www.amazon.%s/gp/product/%s?tag=%s", $tld, getProductId(), $trackingId);
}


function getTld($countryCode) {
	return [
		"AE" => "com.ae",
		"AU" => "com.au",
		"BR" => "com.br",
		"CA" => "ca",
		"CN" => "cn",
		"DE" => "de",
		"ES" => "es",
		"FR" => "fr",
		"IN" => "in",
		"IT" => "it",
		"JP" => "co.jp",
		"MX" => "com.mx",
		"NL" => "nl",
		"SG" => "sg",
		"UK" => "co.uk",
		"US" => "com",
	][$countryCode];
}



if ($_GET["show"] == "urls") {
	$json = [];
	foreach (["AE", "AU", "BR", "CA", "CN", "DE", "ES", "FR", "IN", "IT", "JP", "MX", "NL", "SG", "UK", "US"] as $countryCode) {
		$json[$countryCode] = generateLink($countryCode);
	}

	header("Content-Type: application/json");
	die(json_encode(["urls" => $json]));
}



header("Location: ".generateLink(getCountryCode()));
die();
