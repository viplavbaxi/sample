<?php

require_once('vendor/autoload.php');
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\Exception\TimeoutException;

if (isset($argv[1])) {
  $fileName = "./" . $argv[1];
}
else {
	echo ("No params received \n");
  $fileName = "./testdata_mar24.csv";
}

if(! file_exists($fileName)) exit("No input file exists!\n");

//init $logStr
$logArray = array();
$logArray["Date"]= "Date";
$logArray["PSID"]= "PSID";
$logArray["Password"]= "Password";
$logArray["ADDashboardOK"]= "ADDashboardOK";
$logArray["ADLoginOK"]= "ADLoginOK";
$logArray["CTAs"]= "CTAs";
$logArray["mnCTAs"]= "mnCTAs";
$logArray["LTCTAs"]= "LTCTAs";
$logArray["CountmnCTAStatus"]= "CountmnCTAStatus";
$logArray["CountLTCTAStatus"]= "CountLTCTAStatus";
$logArray["Attendance"]= "Attendance";
$logArray["SubjectsCount"]= "SubjectsCount";
$logArray["Subjects"]= "Subjects";
$logArray["SubjectsListing"]= "SubjectsListing";
$logArray["MNCTAs"]= "MNCTAs";
$logArray["OOPs"]= "OOPs";
$logArray["OOPsURL"]= "OOPsURL";
$logArray["OOPsError"]= "OOPsError";
$logArray["OOPsCompleteError"]="OOPsCompleteError";
writeTestLogsArray($logArray);


$csv = array_map('str_getcsv', file($fileName));
$processedCounter=1;

foreach ($csv as $test) {
  if ($test[0] == "") continue;

  //11 digits
  $PSID = sprintf("%011d", $test[0]);
  $password = $test[1];

  //$PSID = "00002325954";
  //$password = "Vr@160205";
  // $PSID = "00001825935";
  // $password = "Ro@230104";
  // $PSID = "00000151437";
  // $password = "Di@070802";



  echo "$processedCounter - PSID: $PSID, $password\n";

  $maxAttempts = 2;
  $currAttempt=1;
  while($currAttempt<3) {
    try {
      if (checkPSID($PSID, $password, $currAttempt)) break;
    } catch (Exception $e) {
      echo 'Error Occurred: ' .$e->getMessage();
      writeLogs($PSID,'Error Occurred: ' .$e->getMessage());
      $currAttempt++;
    }
  }

  $processedCounter++;
  // finally {
  //   echo "Not making more than $currAttempt attempts: $PSID\n";
  //   writeLogs($PSID, "Attempted $currAttempt times for PSID: $PSID\n");
  // }
  //exit("try end");
  // if ($processedCounter>1) exit("done");
}

function checkPSID($PSID, $password, $attempt){
  $logArray = array();
  $logArray["Date"]= "";
  $logArray["PSID"]= "";
  $logArray["Password"]= "";
  $logArray["ADDashboardOK"]= "";
  $logArray["ADLoginOK"]= "";
  $logArray["CTAs"]= "";
  $logArray["mnCTAs"]= "";
  $logArray["LTCTAs"]= "";
  $logArray["CountmnCTAStatus"]= "";
  $logArray["CountLTCTAStatus"]= "";
  $logArray["Attendance"]= "";
  $logArray["SubjectsCount"]= "";
  $logArray["Subjects"]= "";
  $logArray["SubjectsListing"]= "";
  $logArray["MNCTAs"]= "";
  $logArray["OOPs"]= "";
  $logArray["OOPsURL"]= "";
  $logArray["OOPsError"]= "";
  $logArray["OOPsCompleteError"]="";

  $retVal = true;
  $attemptLog = "$PSID,$attempt";
  try {
      $logs="";
      writeLogs($PSID, "\n--------------------------\nStarting Attempt...$attempt for PSID: $PSID\n--------------------------\n");
      echo "\n--------------------------\nStarting Attempt...$attempt for PSID: $PSID\n--------------------------\n";

      //Initialize WebDriver
      $host = 'http://localhost:4444/wd/hub';
      $web_driver = RemoteWebDriver::create(
      $host,
          array("platform"=>"WINDOWS", "browserName"=>"chrome",
          "version" => "latest", "name" => "First Test"), 120000
      );

      //Configure WebDriver
      $web_driver->manage()->timeouts()->implicitlyWait(3);
      $web_driver->manage()->window()->maximize();

      //Set initial start URL to AD Login page
      $web_driver->get("https://digital.aakash.ac.in/user/login");

      checkPageNotLoaded($web_driver);

      $web_driver->wait(2, 500)->until(
        WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::name("name"))
      );

      //Now at the AD Login Page
      writeLogs($PSID, $web_driver->getTitle()."\n");
      echo $web_driver->getTitle()."\n";
      TakeScreenshot($web_driver, $PSID, 1);

      //writeTestLogs("\n" . date("d-m-Y") . ",$PSID,$password");
      $logArray["Date"] = date("d-m-Y");
      $logArray["PSID"] = $PSID;
      $logArray["Password"] = $password;

      $element = $web_driver->findElement(WebDriverBy::name("name"));
      if($element) {
        $element->sendKeys($PSID);
      }

      $element = $web_driver->findElement(WebDriverBy::name("pass"));
      if($element) {
          $element->sendKeys($password);
          $element->sendKeys(WebDriverKeys::ENTER);
      }

      checkPageNotLoaded($web_driver);

      $web_driver->wait(2, 500)->until(
        WebDriverExpectedCondition::urlIs('https://digital.aakash.ac.in/dashboard')
      );
      //writeTestLogs(",ADLoginOK");
      $logArray["ADLoginOK"] = "Yes";

      //Now at the AD Dashboard
      writeLogs($PSID, $web_driver->getTitle()."\n");
      echo $web_driver->getTitle()."\n";
      TakeScreenshot($web_driver, $PSID, 2);
      //writeTestLogs(",ADDashboardOK");
      $logArray["ADDashboardOK"] = "Yes";

      $web_driver->wait(2, 500)->until(
        WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::xpath("//a[contains(text(),'Attend Live Class')]"))

      );

      //enumerate MN CTAs
      $ctas = "";
      $elements = $web_driver->findElements(WebDriverBy::cssSelector('a.invert-btn.btn'));

      $mnCTAs = 0;
      $LTCTAs = 0;

      foreach ($elements as $element) {
          $link = $element->getAttribute("href");
          if($ctas!= "") {
            $ctas .= "," . $link;
          }  else {
            $ctas .= $link;
          }
          if (strpos($link, "edu.aakashlive.com") > 0) $LTCTAs++;
          if (strpos($link, "meritnation.com/aesl/autologin") > 0) $mnCTAs++;
      }
      writeLogs($PSID, "$ctas, mnCTAs $mnCTAs/ LT CTAs $LTCTAs\n");
      echo $ctas."\n";
      if($ctas) {
        //writeTestLogs("," . count($elements) . " CTA(s), $mnCTAs, $LTCTAs");
        $logArray["CTAs"] = count($elements);
        $logArray["mnCTAs"] = $mnCTAs;
        $logArray["LTCTAs"] = $LTCTAs;
        //writeTestLogs("," . count($elements) . " CTA with $mnCTAs Correct,$LTCTAs CTA going to edu.aakashlive");
        $logArray["CountmnCTAStatus"] = count($elements) . " CTA with $mnCTAs Correct";
        $logArray["CountLTCTAStatus"] = "$LTCTAs CTA going to edu.aakashlive";

      } else {
        writeLogs($PSID, ", No CTA Visible");
      }

      if ($mnCTAs>0) {
        $element = $web_driver->findElement(WebDriverBy::xpath('//a[contains(@href, "https://www.meritnation.com/aesl/autologin/")]'));

        $web_driver->navigate()->to($element->getAttribute("href"));

        //Now at MN Dashboard
        writeLogs($PSID, $web_driver->getTitle()."\n");



        $web_driver->wait(5, 500)->until(
          WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector(".text"))
        );

        TakeScreenshot($web_driver, $PSID, 3);
        //echo "Located subject cards, now getting list...\n";
        //$web_driver->findElement(WebDriverBy::className('card'))->click();
        $attemptLog .= ",MNDashboardOK";

        try {
          //$attendance = $web_driver->findElement(WebDriverBy::xpath("a[contains(text(),'VIEW ATTENDANCE REPORT')]"));
          $attendance = $web_driver->findElement(WebDriverBy::cssSelector(".btn"));


          //writeLogs($PSID, "\n".$attendance->getAttribute('innerHTML')."\n");
          //writeTestLogs("," . $attendance->getAttribute('innerHTML'));
          $yesAttendance = $web_driver->findElement(WebDriverBy::cssSelector('.bulletPoints li:nth-child(1) > .ng-binding'));
          $logArray["Attendance"] = "Total Classes: " . $yesAttendance->getAttribute('innerHTML');
          echo "\n////".$logArray["Attendance"] ."///\n";


        } catch (Exception $e) {
            echo $e->getMessage();
            writeLogs($PSID,"\nCould not get attendance status - ". $e->getMessage());
            $noAttendance = $web_driver->findElement(WebDriverBy::cssSelector('.monthlyAttendanceBox > div:nth-child(4)'));
            $logArray["Attendance"] = $noAttendance->getAttribute('innerHTML');
            echo "\n////".$logArray["Attendance"] ."///\n";
        }



        $elements = $web_driver->findElements(WebDriverBy::className('card'));
        $subjectCards = "";
        foreach ($elements as $element) {
          if($subjectCards!= "") {
            $subjectCards .= "/" . $element->getText();
          } else {
            $subjectCards .= $element->getText();
          }
          //$subjectCards .= $element->getText()."\n";
        }

        writeLogs($PSID, $subjectCards."\n");
        echo $subjectCards."\n";
        $logArray["SubjectsCount"] = count($elements);

        if($subjectCards) {
          $logArray["Subjects"] = $subjectCards;
        } else {
          //writeTestLogs(",, NoSubjects");
          $logArray["Subjects"] = "No Subjects";
        }

        if($elements) {
          // foreach ($elements as $element) {
            //echo $element->getText()."\n";
            // $element->click();
            //break;
          //}
          $elements[0]->click();

          $web_driver->wait(2, 500)->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::className("chap_text"))
          );
          TakeScreenshot($web_driver, $PSID, 4);
          //writeTestLogs(",SubjectPageOK");
          $logArray["SubjectsListing"] = "Yes";
        } else {
          TakeScreenshot($web_driver, $PSID, 4);
          //writeTestLogs(",SubjectPageNA");
          $logArray["SubjectsListing"] = "No";
        }

      } else {
        //write to log that rest have failed
        TakeScreenshot($web_driver, $PSID, 4);
        //writeTestLogs(",No Subjects");
        $logArray["MNCTAs"] = "No";

      }

  }
  catch (Facebook\WebDriver\Exception\TimeoutException $te) {
    $logs .= 'TimeoutException Error Occurred: ' .$te->getMessage();
    echo 'TimeoutException Error Occurred: ' .$te->getMessage();


    writeLogs($PSID, $logs);
    $web_driver->quit();

  }
  catch(Exception $e) {
    $logs .= 'Generic CheckPSID Error Occurred: ' .$e->getMessage();
    echo 'Generic CheckPSID Error Occurred: ' .$e->getMessage();
    writeLogs($PSID, $logs);
    $oops = $web_driver->findElement(WebDriverBy::className('error-page'));
    if ($oops) {
      //var_dump($oops);
      $url = $web_driver->getCurrentUrl();
      echo "\n" . $url."###############\n";
      if ($url == "https://aakashdigital.meritnation.com/authError/106") {
        TakeScreenshot($web_driver, $PSID, 5);

        // <div class="error-page">
        // <span class="oops">OOPS!</span>
        //You will be assigned a batch soon. For any further assistance, please call our support number 8800012998.
        //</div>
        $errorStr = trim($oops->getAttribute('innerHTML'));
        echo str_replace(array("\r\n", "\n", "\r". "\n\r", ","), ' ', $errorStr) . "\n";

        if (strpos($errorStr, "You will be assigned a batch soon")>0) {
          $errorMsg = "Oops - Batches are not assigned";
        } else  {
          $errorMsg = "Unknown Error";
        }
        //writeTestLogs(",,,,,,OOPS, $url," . $errorMsg);
        $logArray["OOPs"] = "Yes";
        $logArray["OOPsURL"] = $url;
        $logArray["OOPsError"] = $errorMsg;
        $logArray["OOPsCompleteError"] = str_replace(array("\r\n", "\n", "\r". "\n\r", ","), ' ', $errorStr);


        $retVal = true;
      }
    } else {
      TakeScreenshot($web_driver, $PSID, 5);
    }

    $web_driver->quit();

  } finally {

    writeLogs($PSID, $logs);
    writeTestLogsArray($logArray);

    $web_driver->quit();


  }
  return $retVal;
  // writeLogs($PSID, $logs);
  // $web_driver->quit();
}

function checkPageNotLoaded($web_driver) {
  try {
    $reloadButton = $web_driver->findElement(WebDriverBy::id('reload-button'));
    while ($reloadButton) {
      echo "\nInternet Not Available - retrying\n";
      $reloadButton->click();
      sleep(2);
      $reloadButton = $web_driver->findElement(WebDriverBy::id('reload-button'));
    }
  } catch (Exception $e) {
    return;
  }

  // if($error->getText().equals("DNS_PROBE_FINISHED_NO_INTERNET") || error.getText().equals("ERR_NAME_NOT_RESOLVED")) {
  //     //System.out.println("No Internet Connection");
  // } else {
  //     //System.out.println("Internet Connected");
  // }

}

function TakeScreenshot($driver, $psid, $stage) {
  // Change the Path to your own settings
  $shotsDir = "./logs/" . $psid;
  if (!is_dir($shotsDir)) {
    //Create our directory if it does not exist
    mkdir($shotsDir);
  }
  $screenshot = $shotsDir . "/$psid-$stage-" . date("d-m-Y-h-i-s") . ".png";

  $driver->takeScreenshot($screenshot);
  if(! file_exists($screenshot)) {
      throw new Exception('Could not save screenshot');
  }

}

function writeLogs($psid, $logs) {
  $shotsDir = "./logs/" . $psid;
  if (!is_dir($shotsDir)) {
    //Create our directory if it does not exist
    mkdir($shotsDir);
  }
  $logFile = $shotsDir . "/$psid" . ".txt";
  $fp = fopen($logFile, 'a');//opens file in append mode
  fwrite($fp, $logs);
  fclose($fp);
}

function writeTestLogs($logs) {
  $shotsDir = "./logs/";
  if (!is_dir($shotsDir)) {
    //Create our directory if it does not exist
    mkdir($shotsDir);
  }
  $logFile = $shotsDir . "/" . date("d-m-Y") . ".csv";
  $fp = fopen($logFile, 'a');//opens file in append mode
  fwrite($fp, $logs);
  fclose($fp);
}

function writeTestLogsArray($logArray) {
  $shotsDir = "./logs/";
  if (!is_dir($shotsDir)) {
    //Create our directory if it does not exist
    mkdir($shotsDir);
  }
  $logFile = $shotsDir . "/" . date("d-m-Y") . ".csv";
  $fp = fopen($logFile, 'a');//opens file in append mode
  $logStr = "";
  $logStr .= $logArray["Date"].",";
  $logStr .= $logArray["PSID"].",";
  $logStr .= $logArray["Password"].",";
  $logStr .= $logArray["ADDashboardOK"].",";
  $logStr .= $logArray["ADLoginOK"].",";
  $logStr .= $logArray["CTAs"].",";
  $logStr .= $logArray["mnCTAs"].",";
  $logStr .= $logArray["LTCTAs"].",";
  $logStr .= $logArray["CountmnCTAStatus"].",";
  $logStr .= $logArray["CountLTCTAStatus"].",";
  $logStr .= $logArray["Attendance"].",";
  $logStr .= $logArray["SubjectsCount"].",";
  $logStr .= $logArray["Subjects"].",";
  $logStr .= $logArray["SubjectsListing"].",";
  $logStr .= $logArray["MNCTAs"].",";
  $logStr .= $logArray["OOPs"].",";
  $logStr .= $logArray["OOPsURL"].",";
  $logStr .= $logArray["OOPsError"].",";
  $logStr .= $logArray["OOPsCompleteError"];
  $logStr .= "\n";

  fwrite($fp, $logStr);
  fclose($fp);
}

    /*
    // foreach ($csv as $test) {
    //
    //   $PSID = "0000" . $test[0];
    //   $password = $test[1];
    //   echo "PSID: $PSID, $password\n";
    //
    // }
    // exit("Done");

    // $num = 123456;
    // $numDigits = strlen((string) $num);
    // $numZeros = 11 - $numDigits;
    // $prefix = str_repeat("0", $numZeros);
    // $PSID = $prefix . $num;
    // printf("%011d", $num);
    // exit("");
    // $PSID = "00002151887";
    // $password = "Sa@290805";
    //
    // $testArray = [
    //   ["00002151887","Sa@290805"],
    //   ["00001872937","aishwarya3"]
    // ];



        $web_driver->wait(10, 1000)->until(
          WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::linkText("Sign In"))
        );

        //$web_driver->manage()->timeouts()->implicitlyWait(3);

        $element = $web_driver->findElement(WebDriverBy::linkText("Sign In"));
        if($element) {
          $element->click();
          //$element->submit();
        }

         if (khagcjhgadgc) {
           echo "\nError - could not find a link!\n";
         }

         $web_driver->wait(10, 500)->until(
           WebDriverExpectedCondition::urlIs('https://aakashdigital.meritnation.com/dashboard/')
         );

         // $web_driver->wait(10, 1000)->until(
         //   WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::name("pass"))
         // );

         //https://www.meritnation.com/aesl/autologin/NTlqV3VsZGNjcHFZLVBld0hfOFdLeHFVZjZfSHFuMUxDZGlSLVVnWnRfayQjJEFFU0xfNWNlYzZiNDljZDE4ZSQjJDgxNDg=
         //xpath=//a[contains(@href, 'https://www.meritnation.com/aesl/autologin/')]

         //$elements = $web_driver->findElements(WebDriverBy::cssSelector('a.invert-btn.btn'));

         foreach ($elements as $element) {

             $link = $element->getAttribute("href");
             echo "Link Found " . $link."\n";
             if (strpos($link, "https://www.meritnation.com/aesl/autologin/")) {
               echo "Clickling " . $link."\n";
               $element->click();
               break;
             }
         }



         $web_driver->wait(10, 1000)->until(
           WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::partialLinkText("/sessionlist/"))
         );
         // $web_driver->wait(10, 1000)->until(
         //   WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::xpath("//a[contains(text(),'View Attendance Report')]"))
         // );


         print $web_driver->getTitle()."\n";


         $web_driver->wait(10, 500)->until(
           WebDriverExpectedCondition::urlContains('https://aakashdigital.meritnation.com/sessionlist/')
         );
         print $web_driver->getTitle()."\n";

         // $elements = $web_driver->findElements(WebDriverBy::tagName('a'));
         // //var_dump($elements);
         // foreach ($elements as $element) {
         //     $link = $element->getAttribute("href");
         //     echo "Link Found " . $link."\n";
         //     if (strpos($link, "/sessionlist/")) {
         //       echo "Clickling " . $link."\n";
         //       $element->click();
         //       break;
         //     }
         // }

         // $web_driver->wait(3, 1000)->until(
         //   WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::className("chapsdsdsd_text"))
         // );

         //echo "Loading MN Dashboard\n";
         // $web_driver->wait(10, 1000)->until(
         //   WebDriverExpectedCondition::urlContains("aakashdigital.meritnation.com/dashboard")
         // );

         // $web_driver->wait(10, 1000)->until(
         //   WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::className('stdntName'))
         // );

         //echo "Loaded MN Dashboard, locating subject cards\n";


     */



?>
