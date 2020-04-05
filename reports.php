<?php
//tail - PowerShell.exe "Get-Content reportsLog.txt -Wait"
require_once('vendor/autoload.php');
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\Exception;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Exception\WebDriverCurlException;
use  Facebook\WebDriver\Exception\ElementNotInteractableException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\UnknownErrorException;


date_default_timezone_set('Asia/Kolkata');


echo "\n--------------------------\nStarting\n--------------------------\n";
//init logs
$logArray = array();
$logArray["Date"]= "Date";
$logArray["LoginCount"]= "LoginCount";
$logArray["TotalQuizAttempts"]= "TotalQuizAttempts";
$logArray["Devices"]= "Devices";
$logArray["Users"]= "Users";
$logArray["Videos"]= "Videos";
$logArray["TestName"]= "Test Name";
$logArray["TestCount"]= "Test Count";
$logArray["Android"]= "Android";
$logArray["Web"]= "Web";
$logArray["Windows"]= "Windows";
// $logArray["VideoSource"]= "Test Count";

writeTestLogsArray($logArray);
$attempts = 0;
while (true) {
    echo  "\nMain: Load WebDriver - Attempt $attempts...  \n";
    try {
      echo  "\nMain: Loading webDriver\n";
      $web_driver = loadDriver();
    } catch (Exception | Facebook\WebDriver\Exception\UnknownErrorException | Facebook\WebDriver\Exception | Facebook\WebDriver\Exception\TimeoutException |  Facebook\WebDriver\Exception\WebDriverCurlException
          | Facebook\WebDriver\Exception\ElementNotInteractableException | Facebook\WebDriver\Exception\NoSuchElementException $eWeb) {
        echo "\nLoading Data: General Error or Facebook\WebDriver\Exception Found: " .$eWeb->getMessage() . "\n";
        unset($web_driver);
    }

    if (!isset($web_driver)) {
      echo  "\nMain: Could not load webDriver on Attempt $attempts...  \n";
      $wait = 1*60;
      sleep($wait);
      $attempts++;
    } else {
      echo  "\nMain: Loaded webDriver Successfully\n";

      $wait = 30*60;
      if (getData($web_driver)) {
        echo  "\nCompleted...\n";
        $wait = 30*60;
        $attempts++;
      } else {
        echo  "\nUnsuccessful - Not Completed...\n";
        $wait = 2*60;
      }

      $web_driver->quit();
      echo  "\nClosed down webDriver\n";

      sleep($wait);
    }

    if ($attempts > 100) break;



}

//all done, cleanup and return
//$web_driver->quit();

function getData($web_driver){
  echo  "\ngetData: Getting new data report...\n";
  $url = "http://reports.aakashitutor.com/";
  if (!loadPage($url, $web_driver, "name", "username")) {
    echo  "\n$url not loaded successfully...exiting\n";
    return false;
  }

  //Login page loaded, now login
  try {
    $user = "viplavbaxi@aesl.in";
    $password = "aakash1234";

    $web_driver->findElement(WebDriverBy::name("username"))->sendKeys($user);
    $web_driver->findElement(WebDriverBy::name("password"))->sendKeys($password);
    $web_driver->findElement(WebDriverBy::name("password"))->sendKeys(WebDriverKeys::ENTER);

    //now wait for home page to load
    $web_driver->wait(10, 500)->until(
      WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector(".text-uppercase"))
      );
    } catch (Exception | Facebook\WebDriver\Exception | Facebook\WebDriver\Exception\TimeoutException |  Facebook\WebDriver\Exception\WebDriverCurlException
          | Facebook\WebDriver\Exception\ElementNotInteractableException | Facebook\WebDriver\Exception\NoSuchElementException $eWeb) {
      echo "\n$url: General Error or Facebook\WebDriver\Exception Found: " .$eWeb->getMessage() . "\n";
      return false;
    }

    //Home Page reached, now get Dashboard
    $url = "http://reports.aakashitutor.com/dashboard/16";
    try {
      if (!loadPage($url, $web_driver, "xpath","//h2[contains(.,'Today Metrics')]")) {
        echo "\n$url not loaded successfully...exiting\n";
        //$web_driver->quit();
        return false;
      }

      // $web_driver->wait(10, 500)->until(
      //   WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::xpath("//h2[contains(.,'Today Metrics')]"))
      //   );
      //find if the stats are visible
      $i = 0;
      for ($i=1;$i<=6;$i++) {
        $check = "cssSelector";
        //$value = ".DashCard:nth-child(" . $i . ") .\_4U6UK > span";
        $value = ".DashCard:nth-child(" . $i . ") .ScalarValue";
        $elementCount = 1;
        if ($i != 5) {
          echo "\nChecking $i $value...\n";
          while (true) {
            $loaded = checkPageElement($url, $web_driver, $check, $value);
            if ($loaded == false && $elementCount >= 2) {
                echo "\n$url: checkElements: Unable to load page element $check, $value even after $elementCount attempts\n";
                return false;
            } elseif ($loaded == false && $elementCount < 2) {
              $elementCount++;
              sleep(1);
              continue;
            }
            elseif ($loaded == true) {
               echo "\n$url: Element $check, $value completed successfully...\n";
                break;
            }
          }
        }

      }


      //find Video source stats
      $i = 0;
      for ($i=1;$i<=3;$i++) {
        $check = "cssSelector";
        $value = ".DashCard:nth-child(5) tr:nth-child(" . $i . ") > .px1:nth-child(2) > .cellData:nth-child(1)";
        $elementCount = 1;

        echo "\nChecking $i $value...\n";
        while (true) {
          $loaded = checkPageElement($url, $web_driver, $check, $value);
          if ($loaded == false && $elementCount >= 2) {
              echo "\n$url: checkElements: Unable to load page element $check, $value even after $elementCount attempts\n";
              return false;
          } elseif ($loaded == false && $elementCount < 2) {
            $elementCount++;
            sleep(1);
            continue;
          }
          elseif ($loaded == true) {
             echo "\n$url: Element $check, $value completed successfully...\n";
              break;
          }
        }


      }

      //find if the most popular 1st test row is available
      $check = "cssSelector";
      //$value = "tr:nth-child(1) > .px1:nth-child(1) > span";
      $value = "tr:nth-child(1) > .px1:nth-child(3) > .cellData";

      $elementCount = 1;
      while (true) {
        $loaded = checkPageElement($url, $web_driver, $check, $value);
        if ($loaded == false && $elementCount >= 2) {
            echo "\n$url: checkElements: Unable to load page element $check, $value even after $elementCount attempts\n";
            return false;
        } elseif ($loaded == false && $elementCount < 2) {
          $elementCount++;
          sleep(1);
          continue;
        }
        elseif ($loaded == true) {
           echo "\n$url: Element $check, $value completed successfully...\n";
            break;
        }
      }

      $web_driver->wait(10, 500)->until(
        WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector(".DashCard:nth-child(7) tr:nth-child(1) > .px1:nth-child(1) > .cellData:nth-child(1)"))
      );


    } catch (Exception | Facebook\WebDriver\Exception | Facebook\WebDriver\Exception\TimeoutException |  Facebook\WebDriver\Exception\WebDriverCurlException
          | Facebook\WebDriver\Exception\ElementNotInteractableException | Facebook\WebDriver\Exception\NoSuchElementException $eWeb) {
        echo "\n$url: Loading Dashboard: General Error or Facebook\WebDriver\Exception Found: " .$eWeb->getMessage() . "\n";
        return false;
    }

    // Read stats from Dashboard
    $logArray = array();
    $logArray["Date"]= date("Y-m-d H:i:s");
    $logArray["LoginCount"]= "";
    $logArray["TotalQuizAttempts"]= "";
    $logArray["Devices"]= "";
    $logArray["Users"]= "";
    $logArray["Videos"]= "";
    $logArray["TestName"]= "";
    $logArray["TestCount"]= "";
    $logArray["Android"]= "";
    $logArray["Web"]= "";
    $logArray["Windows"]= "";

    $i = 1;
    for ($i=1;$i<=6;$i++) {
      if ($i != 5) {

        $element = $web_driver->findElement(WebDriverBy::cssSelector(".DashCard:nth-child(" . $i . ") .ScalarValue"));
        echo "\nGetting $i value...\n";
        switch ($i) {
            case 1:
              $logArray["LoginCount"]= $element->getText();
              break;
            case 2:
                $logArray["Devices"]= $element->getText();
                break;
            case 3:
              $logArray["Users"]= $element->getText();
              break;
            case 4:
              $logArray["Videos"]= $element->getText();
              break;
            case 5:
              // $logArray["Devices"] = "";
              break;

            case 6:
              $logArray["TotalQuizAttempts"]= $element->getText();
              break;
          default:
              echo "Entering default!";
          }
        }

      }


      $i = 1;
      for ($i=1;$i<=3;$i++) {

        $element = $web_driver->findElement(WebDriverBy::cssSelector(".DashCard:nth-child(5) tr:nth-child(" . $i . ") > .px1:nth-child(2) > .cellData:nth-child(1)"));
        echo "\nGetting $i value...\n";
        switch ($i) {
            case 1:
              $logArray["Android"]= $element->getText();
              break;
            case 2:
                $logArray["Web"]= $element->getText();
                break;
            case 3:
              $logArray["Windows"]= $element->getText();
              break;

          default:
              echo "Entering default!";
          }

        }




    // $element = $web_driver->findElement(WebDriverBy::cssSelector("tr:nth-child(1) > .px1:nth-child(1) > span"));
    // $element = $web_driver->findElement(WebDriverBy::cssSelector(".DashCard:nth-child(7) tr:nth-child(2) > .px1:nth-child(1) > span:nth-child(1)"));
    $element = $web_driver->findElement(WebDriverBy::cssSelector(".DashCard:nth-child(7) tr:nth-child(1) > .px1:nth-child(1) > .cellData:nth-child(1)"));
    $logArray["TestName"]= $element->getText();

    // $element = $web_driver->findElement(WebDriverBy::cssSelector("tr:nth-child(1) > .px1:nth-child(3) > span"));
    $element = $web_driver->findElement(WebDriverBy::cssSelector("tr:nth-child(1) > .px1:nth-child(3) > .cellData"));
    $logArray["TestCount"]= $element->getText();

    //logs obtained, now write to log file
    echo "\nWriting Logs...\n";
    var_dump($logArray);
    writeTestLogsArray($logArray);
    TakeScreenshot($web_driver, "Dashboard");
    echo ".";

    sleep(1);

    return true;
}

function loadDriver() {

  try {
      //Initialize WebDriver
      echo  "\nLoadDriver: Initializing webDriver...\n";
      $host = 'http://localhost:4444/wd/hub';
      $web_driver = RemoteWebDriver::create(
      $host,
          array("platform"=>"WINDOWS", "browserName"=>"chrome",
          "version" => "latest", "name" => "First Test"), 120000
      );
      echo  "\nLoadDriver: Configuring webDriver...\n";
      //Configure WebDriver
      $web_driver->manage()->timeouts()->implicitlyWait(3);
      $web_driver->manage()->window()->maximize();
    } catch (Exception | Facebook\WebDriver\Exception | Facebook\WebDriver\Exception\TimeoutException |  Facebook\WebDriver\Exception\WebDriverCurlException
              | Facebook\WebDriver\Exception\ElementNotInteractableException | Facebook\WebDriver\Exception\NoSuchElementException $eWeb) {
      echo "\nLoadDriver: Configuring web driver: Facebook\WebDriver\Exception Error Occurred: " .$eWeb->getMessage() . "\n";
      return null;
    }
  return $web_driver;
}

function checkPageElement($url, $web_driver, $check, $value) {
  echo  "\nNow checking page elements...\n";

  $loaded = false;
  $attempts = 0;
  //page loaded, check if the desired element is available
  while (!$loaded && $attempts < 3) {
    try {
      if (($check == "cssSelector" || $check == "xpath" || $check =="name") && $value) {
        echo  "\nChecking $check, $value\n";

          switch ($check) {
            case "cssSelector":
              $web_driver->wait(2, 500)->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector($value))
              );
              TakeScreenshot($web_driver, "Dashboard");
              $loaded = true;
              break;
            case "xpath":
              $web_driver->wait(2, 500)->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::xpath($value))
              );
              TakeScreenshot($web_driver, "Dashboard");
              $loaded = true;
              break;

            case "name":
                $web_driver->wait(2, 500)->until(
                  WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::name($value))
                );
                TakeScreenshot($web_driver, "Dashboard");
                $loaded = true;
                break;
          default:
              echo "Entering default! Check criteria $check : $value invalid\n";
              break;
          }
        }
        echo  "\nAttempt $attempts completed\n";
        $attempts++;
      } catch (Facebook\WebDriver\Exception\NoSuchElementException | Facebook\WebDriver\Exception | Facebook\WebDriver\Exception\ElementNotInteractableException  $eWeb) {
          echo 'Element Loading Error: Facebook\WebDriver\Exception Error Occurred: ' .$eWeb->getMessage() . "\n";
          //could not find the element, let's wait a bit before retrying
          TakeScreenshot($web_driver, "Dashboard");
          sleep(1);
      } catch (Exception | Facebook\WebDriver\Exception\TimeoutException |
              Facebook\WebDriver\Exception\WebDriverCurlException
              $eWeb) {
          echo "\nGeneric or Timeout Loading Error: $url, $check, $value \n Facebook\WebDriver\Exception Error Occurred: " .$eWeb->getMessage() . "\n";
          //something else occurred, let us gracefully exit
          $loaded = false;
          TakeScreenshot($web_driver, "Dashboard");
          break;
      }
      $attempts++;
      return $loaded;
  }
}

function loadPage($url, $web_driver, $check, $value) {
  $attempts = 0;
  $loaded = false;
  echo  "\nLoading Page...$url\n";


  while ($attempts < 5) {
    try {
        $web_driver->get($url);
        $web_driver->wait(2, 500)->until(
          WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('reload-button'))
        );
        TakeScreenshot($web_driver, "Dashboard");
        $reloadButton = $web_driver->findElement(WebDriverBy::id('reload-button'));
        if ($reloadButton) {
          echo  "\nInternet Not Available - retrying\n";
          $reloadButton->click();
          sleep(1);
          $attempts++;
        } else {
          $loaded = true;
          break;
        }
      } catch (Facebook\WebDriver\Exception\NoSuchElementException | Facebook\WebDriver\Exception | Facebook\WebDriver\Exception\ElementNotInteractableException $eWeb) {
          echo "\n$url: loadPage Error: Facebook\WebDriver\Exception  Occurred: " .$eWeb->getMessage() . "\n";
          //did not find the reload button. Some page content has loaded ok.
          TakeScreenshot($web_driver, "Dashboard");
          echo "\n$url: Page loaded\n";
          $loaded = true;
          break;
      } catch (Exception  | Facebook\WebDriver\Exception\TimeoutException |
              Facebook\WebDriver\Exception\WebDriverCurlException
              $eWeb) {
          echo "\n$url: loadPage Error: Timeout Exception or CurlException Occurred: " .$eWeb->getMessage() . "\n";
          TakeScreenshot($web_driver, "Dashboard");
          //some other network or driver issue, let's reload in a bit
          sleep(1);

      }
      $attempts++;
  }

  if ($loaded == false) {
    echo  "\n$url: getURL: Unable to load page $url even after $attempts attempts\n";
    return false;
  }

  $loaded = checkPageElement($url, $web_driver, $check, $value);
  if ($loaded == false) {
      echo  "\n$url: checkElements: Unable to load page $url even after $attempts attempts\n";
      return false;
  } else {
     echo  "\n$url: Element check completed successfully...\n";
      return true;
  }


}

function TakeScreenshot($driver, $folder) {
  // Change the Path to your own settings
  $shotsDir = "./logs/" . $folder . "/" . date("d-m-Y");
  if (!is_dir($shotsDir)) {
    //Create our directory if it does not exist
    mkdir($shotsDir);
  }
  $screenshot = $shotsDir . "/" . date("d-m-Y-h-i-s") . ".png";
  try {
    $driver->takeScreenshot($screenshot);
  } catch (Facebook\WebDriver\Exception\NoSuchElementException | Facebook\WebDriver\Exception | Facebook\WebDriver\Exception\ElementNotInteractableException $eWeb) {
      echo "\n$screenshot: TakeScreenShot Error: Facebook\WebDriver\Exception  Occurred: " .$eWeb->getMessage() . "\n";
  } catch (Exception  | Facebook\WebDriver\Exception\TimeoutException |
          Facebook\WebDriver\Exception\WebDriverCurlException
          $eWeb) {
      echo "\n$screenshot: TakeScreenShot Error: Timeout Exception or CurlException Occurred: " .$eWeb->getMessage() . "\n";
  }

}

function writeTestLogsArray($logArray) {
  $shotsDir = "./logs/Dashboard/" . date("d-m-Y");
  if (!is_dir($shotsDir)) {
    //Create our directory if it does not exist
    mkdir($shotsDir);
  }
  $logFile = $shotsDir . "/report-". date("d-m-Y") . ".csv";
  try {

    $fp = fopen($logFile, 'a');//opens file in append mode
    fputcsv($fp, $logArray);
    fclose($fp);
  } catch (Exception $e) {
    echo "\nError occurred writing to log file: " . $e->getMessage() . "\n";
    var_dump($logArray);
  }
}

/*
//page loaded, check if the desired element is available
if ($check && $value) {
    switch ($check) {
      case "cssSelector":
        $web_driver->wait(2, 500)->until(
          WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector($value))
        );
        break;
      case "xpath":
        $web_driver->wait(2, 500)->until(
          WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::xpath($value))
        );
        break;
    default:
        echo "Entering default!";
    }

*/
?>
