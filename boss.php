<?php

class boss
{

    public $lv = 0;

    public $hp = 0;

    public $temphp = 0;

    private $dehit = 0; // 闪避

    private $decri = 9999999; // 韧性-防暴击

    private $mdef = 0; // 魔防

    private $def = 0; // 物防

    private $protect = 0; // 护甲

    /**
     * Boss
     * 
     * @param $bosslv Boss等级            
     * @param $bosshp Boss总血量            
     * @param $bosstemphp Boss当前血量            
     */
    function __construct($bosslv, $bosshp, $bosstemphp)
    {
        $this->lv = $bosslv;
        $this->hp = $bosshp;
        $this->temphp = $bosstemphp;
    }

    /**
     * 被攻击
     * ret:1普攻 2暴击 3miss 4无法攻击 5回血 6buff 7回蓝
     * 
     * @param
     *            $atk
     * @return int
     */
    public function onatk($atk)
    {
        $ret = 1;
        $ret0 = 1;
        $A_ret = $atk[0];
        $skill = $atk[1];
        $A_atkvalue = $atk[2];
        $A_lv = $atk[3];
        $A_hit = $atk[4];
        $A_cri = $atk[5];
        $A_criatk = $atk[6];
        $A_cri100 = $atk[7];
        $A_deprotect = $atk[8];
        $A_xixue = $atk[14]; // 攻击吸血
        $A_sid = $skill['sid'];
        $A_stype = $skill['stype'];
        $A_satk = $skill['satk'];
        $A_buffid = $skill['buffid'];
        $A_buffrate = $skill['buffrate'] ? $skill['buffrate'] : 0;
        $A_buffstep = $skill['buffstep'];
        if ($A_stype > 3) {
            return 0;
        }
        // 普通攻击
        if ($A_stype == 1) {
            // 判断命中
            if (rand(1, 10000) <= (($A_hit + 1) * 10000 / ($A_hit + $this->dehit + 1))) {
                $ret = 1;
            } else {
                $ret = 3;
                $A_satk = 0;
            }
            // 判断暴击
            if ($A_cri100 && $ret != 3) {
                $ret = 2;
            } else {
                if ($A_cri > $this->decri && $ret != 3) {
                    if (rand(1, 10000) <= (($A_cri - $this->decri) * 10000 / ($A_cri - $this->decri + $this->lv * ($this->lv + 1) * 5 + 50))) {
                        $ret = 2;
                    } elseif ($ret != 3) {
                        $ret = 1;
                    }
                } elseif ($ret != 3) {
                    $ret = 1;
                }
            }
            $xixue = 1;
        }        // 魔法/物理攻击,新增暴击
        elseif ($A_stype == 2 || $A_stype == 3) {
            $xixue = 1;
            // 判断暴击
            if ($A_cri > $this->decri) {
                if (rand(1, 10000) <= (($A_cri - $this->decri) * 10000 / (2 * ($A_cri - $this->decri + $this->lv * ($this->lv + 1) * 3 + 50)))) {
                    $ret = 2;
                }
            }
        }
        
        if ($ret == 1) {
            // 爆击伤害百分比
            $A_criatk = 10000;
        }
        
        // 这里只有物防，注意添加魔防
        // 额外3 护甲 护甲 魔防 物防
        // 原来护甲*护甲增强*被破甲*无视护甲
        $B_protect = $this->protect * (10000 - $A_deprotect) / 10000;
        // 最终伤害减免
        $A_atk3 = $B_protect / ($B_protect + ($this->lv + 1) * 50);
        // 额外4 物抗魔抗抵伤
        // 魔抗
        $A_atk4_mdef = $this->mdef / ($this->mdef + ($A_lv + 1) * 100);
        // 物抗
        $A_atk4_def = $this->def / ($this->def + ($A_lv + 1) * 100);
        // 额外5 伤害减免
        $B_atk5 = 1;
        
        switch ($A_stype) {
            case 1:
            case 2:
                $value = $A_atkvalue * ($A_criatk / 10000) * ($A_satk / 10000) * (1 - $A_atk3) * (1 - $A_atk4_def) * $B_atk5;
                break;
            case 3:
                $value = $A_atkvalue * ($A_criatk / 10000) * ($A_satk / 10000) * (1 - $A_atk3) * (1 - $A_atk4_mdef) * $B_atk5;
                break;
            case 4:
                $ret = 4;
                $value = $this->hp * ($A_satk / 10000);
                break;
            case 5:
                $value = $this->hp * ($A_satk / 10000);
                break;
            case 6:
                $ret = 6;
                $value = $A_atkvalue * ($A_criatk / 10000) * ($A_satk / 10000) * (1 - $A_atk3) * (1 - $A_atk4_def) * $B_atk5;
                break;
            case 7:
                $ret = 7;
                break;
            default:
                $value = $A_atkvalue * ($A_criatk / 10000) * ($A_satk / 10000) * (1 - $A_atk3) * (1 - $A_atk4_def) * $B_atk5;
                break;
        }
        if ($atk[10] == 16) {
            // 附加50%魔法伤害;
            $value = $value + $A_atkvalue * ($A_criatk / 10000) * (5000 / 10000) * (1 - $A_atk3) * (1 - $A_atk4_mdef) * $B_atk5;
        }
        if ($A_stype != 6 && $A_stype != 7 && $value < 1 && $ret != 3) {
            // 1点强制伤害
            $value = 1;
        }
        $value = floor($value);
        $value = min($this->temphp, $value);
        $this->temphp -= $value;
        return $value;
    }
}

?>
