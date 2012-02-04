<?php

namespace Orm\Builder;

require_once __DIR__ . '/inc/boot.php';

$isDev = isset($_GET['dev']);
$info = new VersionInfo(new Git(__DIR__ . '/..'), $isDev, $isDev ? $_GET['dev'] : NULL);

$zip = new Zipper(__DIR__ . "/Orm-{$info->tag}.zip", __DIR__, $info->versionId !== -1);

$b = new Builder(Builder::NS | Builder::NS_NETTE, $info);
$b->build(__DIR__ . "/../Orm", __DIR__ . "/php53/Orm");
$zip->add($b);

$b = new Builder(Builder::NONNS | Builder::NONNS_NETTE, $info);
$b->build(__DIR__ . "/../Orm", __DIR__ . "/php52/Orm");
$zip->add($b);

if ($info->versionId !== -1)
{
	$api = new Api;
	$api->generate(__DIR__ . "/php52/Orm", __DIR__ . "/php52/Api");
	$api->generate(__DIR__ . "/php53/Orm", __DIR__ . "/php53/Api");

	$zip->add($api);

	$zip->add(new Readme(__DIR__ . "/../README.md", __DIR__ . '/README', $info));
}

$zip->save();

if ($info->versionId !== -1)
{
	@mkdir(__DIR__ . '/ftp'); @mkdir(__DIR__ . '/ftp/api'); @mkdir(__DIR__ . '/ftp/download');
	mkdir(__DIR__ . "/ftp/api/{$info->tag}");
	rename(__DIR__ . "/Orm-{$info->tag}.zip", __DIR__ . "/ftp/download/Orm-{$info->tag}.zip");
	rename(__DIR__ . '/php52/Api', __DIR__ . "/ftp/api/{$info->tag}/php52");
	rename(__DIR__ . '/php53/Api', __DIR__ . "/ftp/api/{$info->tag}/php53");
}

echo "<h1>{$info->tag}<h1>{$info->shortSha}<br>{$info->versionId}";

foreach (array(
	Builder::NS | Builder::NS_NETTE => 'php53/Nette_with_namespaces',
	Builder::NS | Builder::NONNS_NETTE => 'php53/Nette_without_namespaces',
	//Builder::NS | Builder::PREFIXED_NETTE => 'php53/Nette_prefixed',
	Builder::NONNS | Builder::NONNS_NETTE => 'php52/Nette_without_namespaces',
	Builder::NONNS | Builder::NS_NETTE => 'php52/Nette_with_namespaces',
	//Builder::NS | Builder::PREFIXED_NETTE => 'php52/Nette_prefixed',
) as $version => $dir)
{
	$b = new Builder($version, $info);
	foreach (array(
		'tests/cases',
		'tests/boot.php',
		'tests/loader.php',
		'tests/libs/HttpPHPUnit',
		'tests/libs/dump.php',
	) as $p)
	{
		$b->build(__DIR__ . "/../$p", __DIR__ . "/$dir/$p");
	}
}

$partialSupport = new PartialSupportTestsConverter(__DIR__ . '/php52/Nette_without_namespaces/tests', __DIR__ . '/php52/Nette_without_namespaces_partial/tests');
$partialSupport->convert();

echo '<h1>OK<h1>';
