<?php
declare(strict_types=1);

use Controllers\IndexController;

/* @var $this IndexController */
?>
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


<?php $this->renderPartial('test.php', ['data' => 'View Partial Data']); ?>
