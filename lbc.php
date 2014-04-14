<?php
require __DIR__. '/PHPMailer.php';

$t_params = Array(
            'input-encoding' => 'utf8',
            'output-encoding' => 'utf8',
            'fix-bad-comments' => false,
            'fix-uri' => false,
            'hide-comments' => true,
            'literal-attributes' => true,
            'output-xhtml' => true,
            'indent' => true,
            'indent-attributes' => true
            );


$items = Array();
$urls = Array();
$urls[] = 'http://www.leboncoin.fr/locations/offres/rhone_alpes/?f=a&th=1&mre=1200&sqs=6&location=Toutes%20les%20communes%2074100';
$urls[] = 'http://www.leboncoin.fr/locations/offres/rhone_alpes/?f=a&th=1&mre=1200&sqs=6&location=Toutes%20les%20communes%2074160';

foreach($urls as $url){
  sleep(rand(1,5));
  $c = file_get_contents($url);
  $c = str_replace(Array('&nbsp;', "\r", "\t", "\n" ), Array(' ', '', ' ', ''), $c);
  $c = preg_replace('#<!--.*-->#Ui', '', $c);
  $c = preg_replace('#<\s*script.*>.*<\s*/\s*script\s*>#Ui', '', $c);
  $c = preg_replace('#<\s*style.*>.*<\s*/\s*style\s*>#Ui', '', $c);
  $c = preg_replace('#\s+#', ' ', $c);

  $tidy = new Tidy();
            
  $c = $tidy->repairString($c, $t_params, 'utf8'); 


  $oXml = new DOMDocument();
  $oXml->preserveWhiteSpace = false;
  $oXml->loadXML($c);
  $x   = new DOMXPath($oXml);
  $x->registerNamespace('h', 'http://www.w3.org/1999/xhtml');
       
  $t = $x->query('//h:div[@class="list-lbc"]/h:div[@class="lbc"]'); 
  foreach($t as $v)
  { 
    $url = $v->previousSibling->attributes->getNamedItem('href')->nodeValue;
    $date =  str_replace(Array('Hier','Aujourd\'hui'), Array('yesterday','today'), preg_replace('#\s+#', ' ', $v->childNodes->item(0)->nodeValue));
    $ts = strtotime($date);
    $title = preg_replace('#\s+#', ' ', $v->childNodes->item(0)->nextSibling->nextSibling->childNodes->item(0)->nodeValue);
  
    if( $ts > time() - 46*60 && $ts < time() - 14*60  ){
      $items[] = '<a href="'.$url.'">'.$title.'</a>';
    }
  }
}

if($items){
  // Sujet
  $subject = 'Nouvelles annonces!';

  // message
  $message = '
     <html>
      <head>
        <meta charset="utf-8">
      </head>
      <body>
       <p>Nouvelles annonces sur le bon coin:</p>
       <ul>
        <li>
         ' . implode('</li><li>', $items) . '
        </li>
       </ul>
      </body>
     </html>';

  $oMail = new PHPMailer();
  $oMail->SetFrom('moi@moi.fr', 'Moi');
  $oMail->AltBody = "To view the message, please use an HTML compatible email viewer!";
  $oMail->Subject = $subject;
  $oMail->MsgHTML($message);
  $oMail->AddAddress('toi@toi.fr');
  $oMail->Send();
}
