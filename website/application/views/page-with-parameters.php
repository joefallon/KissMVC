<?php /** @var $this PageWithParametersPresenter */ ?><pre>
<?php
$reqParams = $this->getRequestParameters();

print_r($reqParams);
echo "\n";
echo 'request parameter with index 1 = ';
print_r($reqParams[1]);

?>
</pre>
