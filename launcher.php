<?php

@setlocale(LC_ALL, "en_US.UTF-8");


define ('PHP_CMD', "/usr/bin/php");

class AlsongLyric
{
    const AlsongLyricListSoap = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://www.w3.org/2003/05/soap-envelope\" xmlns:SOAP-ENC=\"http://www.w3.org/2003/05/soap-encoding\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:ns2=\"ALSongWebServer/Service1Soap\" xmlns:ns1=\"ALSongWebServer\" xmlns:ns3=\"ALSongWebServer/Service1Soap12\"><SOAP-ENV:Body><ns1:GetResembleLyricList2><ns1:encData>8456ec35caba5c981e705b0c5d76e4593e020ae5e3d469c75d1c6714b6b1244c0732f1f19cc32ee5123ef7de574fc8bc6d3b6bd38dd3c097f5a4a1aa1b438fea0e413baf8136d2d7d02bfcdcb2da4990df2f28675a3bd621f8234afa84fb4ee9caa8f853a5b06f884ea086fd3ed3b4c6e14f1efac5a4edbf6f6cb475445390b0</ns1:encData><ns1:title>%title%</ns1:title><ns1:artist>%artist%</ns1:artist><ns1:pageNo>1</ns1:pageNo></ns1:GetResembleLyricList2></SOAP-ENV:Body></SOAP-ENV:Envelope>";
    const AlsongLyricByIDSoap = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://www.w3.org/2003/05/soap-envelope\" xmlns:SOAP-ENC=\"http://www.w3.org/2003/05/soap-encoding\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:ns2=\"ALSongWebServer/Service1Soap\" xmlns:ns1=\"ALSongWebServer\" xmlns:ns3=\"ALSongWebServer/Service1Soap12\"><SOAP-ENV:Body><ns1:GetLyricByID2><ns1:encData>8456ec35caba5c981e705b0c5d76e4593e020ae5e3d469c75d1c6714b6b1244c0732f1f19cc32ee5123ef7de574fc8bc6d3b6bd38dd3c097f5a4a1aa1b438fea0e413baf8136d2d7d02bfcdcb2da4990df2f28675a3bd621f8234afa84fb4ee9caa8f853a5b06f884ea086fd3ed3b4c6e14f1efac5a4edbf6f6cb475445390b0</ns1:encData><ns1:lyricID>%lyric_id%</ns1:lyricID></ns1:GetLyricByID2></SOAP-ENV:Body></SOAP-ENV:Envelope>";
        
    public function Log($text)
    {
        /*
        $script = escapeshellarg(__DIR__ . '/logwrite.php');
        $cmd = PHP_CMD . " ${script} --action Log --text \"${text}\"";
        exec($cmd, $output);
        */
    }

    public function getAlsongLyricForID($id)
    {
        $soapData = str_replace(array('%lyric_id%'), array($id), self::AlsongLyricByIDSoap);

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'timeout' => 10,
                'header'  => 'Content-Type: text/xml;charset=utf-8',
                'SOAPAction' => '\"ALSongWebServer/GetLyricByID2\"',
                'User-Agent' => 'gSOAP/2.7',
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

    public function getAlsongLyricList($artist, $title)
    {
        $soapData = str_replace(array('%artist%', '%title%'), array($artist, $title), self::AlsongLyricListSoap);

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'timeout' => 10,
                'header'  => 'Content-Type: text/xml;charset=utf-8',
                'SOAPAction' => '\"ALSongWebServer/GetResembleLyricList2\"',
                'User-Agent' => 'gSOAP/2.7',
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
        $content = $this->getAlsongLyricList($artist, $title);

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

        $xmlLyric = $xml->Body->GetResembleLyricList2Response->GetResembleLyricList2Result->ST_SEARCHLYRIC_LIST;

        foreach ($xmlLyric as $item) {
            $albumText = '[Album] '. (string)$item->album;
            $id = json_encode(array(
                'artist' => (string)$item->artist,
                'title' => (string)$item->title,
                'id' => (int)$item->lyricID));

            $info->addTrackInfoToList(    (string)$item->artist, 
                                        (string)$item->title, 
                                        $id, 
                                        (string)$item->strRegisterName);
        };

        return $xmlLyric->count();
    }
    
    public function getLyricsDebug($id)
    {
        $content = $this->getAlsongLyricForID($id);

        if( $content == NULL || $content == "" )
        {
            $this->Log("Get alsong server fail.");
            return true;
        }

        $lyricId = (string)$id;

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);

        if( $xml === FALSE )
        {
            $this->Log("XML parsing fail.");
            return true;
        }

        $xmlLyric = $xml->Body->GetLyricByID2Response->output;

        $this->Log($xmlLyric);
        
        $InfoId = (string)$xmlLyric->lyricID;
        $regName = (string)$xmlLyric->registerName;
        $modifiName = (string)$xmlLyric->modifierName;
        $lyricAddText = "등록 : " . $regName . "\n수정 : " . $modifiName;

        $lyricText = (string)$xmlLyric->lyric . $lyricAddText;

        $this->Log($lyricText);

        return $lyricText;
    }

    public function getLyrics($id, $info)
    {
        $data = json_decode($id, TRUE);
        if (NULL === $data) {
            $info->addLyrics("[Alsong Lyric] JSON decoding fail.", $id);
            $this->Log("JSON decoding fail.");
            return true;
        }

        $content = $this->getAlsongLyricForID($data['id']);

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

        $xmlLyric = $xml->Body->GetLyricByID2Response->output;
        
        $InfoId = (string)$xmlLyric->lyricID;
        $regName = (string)$xmlLyric->registerName;
        $modifiName = (string)$xmlLyric->modifierName;
        $lyricAddText = "등록 : " . $regName . "\n수정 : " . $modifiName;
        $lyricText = (string)$xmlLyric->lyric . $lyricAddText;

        $info->addLyrics($lyricText, $id);

        return true;
    }

}

?>
