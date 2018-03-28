<?php
/**
 * 简单的抽奖概率函数
 * @param array $rewardArray 概率,如：$rewardArray = array(10, 20, 20, 30, 10, 10)，对应各奖品的概率
 * @return int    概率数组的下标
 */
//$rewardArray = array(5000, 0, 0, 0, 0, 5000);
//echo luckDraw($rewardArray);
function luckDraw($rewardArray)
{
    set_time_limit(0);
    $sum = array_sum($rewardArray);
    
    if($sum != 10000)
    {
        return 0;
    }

    //获取随机数
    $rewardNum = mt_rand(0, $sum - 1);
    $totalnum = 0;
    for($i = 0; $i < count($rewardArray); $i++)
    {
        $totalnum += $rewardArray[$i];
        if ($rewardNum <= $totalnum){
            return $i;
        }
    }   
}