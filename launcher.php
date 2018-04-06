<?php

@setlocale(LC_ALL, "en_US.UTF-8");


define ('PHP_CMD', "/usr/bin/php");

class AlsongLyric
{
	const AlsongSoapXml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://www.w3.org/2003/05/soap-envelope\" xmlns:SOAP-ENC=\"http://www.w3.org/2003/05/soap-encoding\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:ns2=\"ALSongWebServer/Service1Soap\" xmlns:ns1=\"ALSongWebServer\" xmlns:ns3=\"ALSongWebServer/Service1Soap12\"><SOAP-ENV:Body><ns1:GetResembleLyric2><ns1:stQuery><ns1:strTitle>%title%</ns1:strTitle><ns1:strArtistName>%artist%</ns1:strArtistName><ns1:nCurPage>0</ns1:nCurPage></ns1:stQuery></ns1:GetResembleLyric2></SOAP-ENV:Body></SOAP-ENV:Envelope>";

		
	public function Log($text)
	{
		/*
		$script = escapeshellarg(__DIR__ . '/logwrite.php');
		$cmd = PHP_CMD . " ${script} --action Log --text \"${text}\"";
		exec($cmd, $output);
		*/
	}
	

	public function getContent($artist, $title)
    {
        $soapData = str_replace(array('%artist%', '%title%'), array($artist, $title), self::AlsongSoapXml);

		$opts = array('http' =>
		    array(
        		'method'  => 'POST',
				'timeout' => 10,
        		'header'  => 'Content-Type: text/xml;charset=utf-8',
        		'content' => $soapData
    		)
		);

		$context = stream_context_create($opts);

		$content = @file_get_contents("http://lyrics.alsong.co.kr/alsongwebservice/service1.asmx", false, $context);

		$result = $content;

		$result = str_replace('soap:', '', $result);
		$result = str_replace('xmlns=', 'ns=', $result);

        return $result;
    }

	public function getLyricsList($artist, $title, $info)
	{
		$content = $this->getContent($artist, $title);

		if( $content == NULL || $content == "" )
		{
			$this->Log("Alsong Server - Get Content Fail.");
			return 0;
		}

		libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);

        if( $xml === FALSE )
        {
			$this->Log("Alsong Server - XML Load Fail.");
			$this->Log($content);

            return 0;
		}

		$xmlLyric = $xml->Body->GetResembleLyric2Response->GetResembleLyric2Result->ST_GET_RESEMBLELYRIC2_RETURN;

		foreach ($xmlLyric as $item) {
			$lyricText = $licenseText . str_replace('<br>', "\n", $item->strLyric);
			$lyricText = mb_strimwidth($lyricText, '0', '10', '...', 'utf-8');
			$id = json_encode(array(
				'artist' => (string)$item->strArtistName,
				'title' => (string)$item->strTitle,
				'id' => (int)$item->strInfoID));

			$info->addTrackInfoToList(	(string)$item->strArtistName, 
										(string)$item->strTitle, 
										$id, 
										(string)$item->strRegisterName);
		};

		return $xmlLyric->count();
	}

	public function getLyrics($id, $info)
	{
		$data = json_decode($id, TRUE);
		if (NULL === $data) {
			$info->addLyrics("[Alsong Lyric] JSON decoding fail.", $id);
			$this->Log("JSON decoding fail.");
			return true;
		}

		$content = $this->getContent($data['artist'], $data['title']);

		if( $content == NULL || $content == "" )
		{
			$info->addLyrics("[Alsong Lyric] Get Alsong server fail.", $id);
			$this->Log("Get alsong server fail.");
			return true;
		}

		$lyricId = (string)$data['id'];

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);

        if( $xml === FALSE )
        {
			$info->addLyrics("[Alsong Lyric] XML parsing fail.", $id);
			$this->Log("XML parsing fail.");
            return true;
        }

		$xmlLyric = $xml->Body->GetResembleLyric2Response->GetResembleLyric2Result->ST_GET_RESEMBLELYRIC2_RETURN;

		$xmlCount = sprintf("xml count:%d", $xmlLyric->count());
		foreach ($xmlLyric as $item) {
			$InfoId = (string)$item->strInfoID;
            if( $InfoId === $lyricId ){
				//$licenseText = "[00:00:00]Alsong 가사 플러그인 시험중입니다.\n";
				$lyricText = $licenseText . str_replace('<br>', "\n", $item->strLyric);
				$info->addLyrics($lyricText, $id);
				return true;
			}
		}

		$info->addLyrics("[Alsong Lyric] XML count 0.", $id);
		$this->Log("XML count 0.");
		return true;
	}

}

?>
