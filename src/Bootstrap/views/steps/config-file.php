<?php
    use Magrathea2 as Mag2;

    $bootstrapClass = Mag2\Bootstrap\Start::Instance();
    $bootstrapClass->CreateDefaultConfigFile();
?>

<pre class="code">
<?php $bootstrapClass->ViewConfigFile(); ?>
</pre>

You can edit this config file anytime.

<br/><br/>
<hr/>