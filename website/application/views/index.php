<?php /* @var $this \Application\Controllers\IndexController */ ?>
<pre>

Main View:

<?php echo $this->getMessage(); ?>

</pre>

<ul>
    <li>
        <a href="/page-with-parameters/abc/123/xyz">
            Page with Parameters
        </a>
    </li>
</ul>



<?php $this->renderPartial('test.php', array('data' => 'View Partial Data')); ?>
