# PhpProgressLogger
Easy-to-use PHP Logger for advanced logging of progress &amp; time left in scripts.

# packagist:
https://packagist.org/packages/vannieuwenhovej/php-progress-logger

# Installation
`composer require vannieuwenhovej/php-progress-logger`

# Example / Usage
      $logger = new vannieuwenhovej\ProgressLogger($total, 500);
      $totalDone= 0;
and in loop:

       foreach($objects as $object){
           //do something;
           $totalDone++; 
           $logger->log($totalDone);
        }
