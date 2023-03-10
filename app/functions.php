<?php
function tpl($tpl)
{
    return VIEW . str_replace('.', DS, $tpl) . '.php';
}
