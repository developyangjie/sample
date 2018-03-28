<?php

class my
{
    // 一级显示属性-血
    public $uid = 0; // uid
    
    public $lv = 0; // 等级

    public $hp = 0; // 血量

    public $picindex = 0; // 图片索引

    public $zhanli =0; // 战力
//     public $zhanli = 0; // 战力

    // 二级基础属性
    public  $baseatk=0;
    public  $basehp=0;
    public  $basedef =0;
    public  $basemdef =0;
    public  $basecri =0;
    public  $basecure =0;

    // 三级战斗属性
    public $mainv = 0;  //主属性
    
    public $atk = 0;  //攻击力
    
    public $mdef = 0; // 魔防

    public $def = 0; // 物防

    public $cri = 0; // 暴击

    public $cure = 0; // 治疗

    public $name = "未知目标"; // 名字
    /**
     *
     * @param $t 人物标识            
     * @param $myinfo 个人信息            
     * @param $myequip 装备信息            
     */
    function __construct($t, $myinfo,$myequip)
    {
        if($t == 1){
            $this->uid = $myinfo['uid'];
            $this->lv = intval($myinfo['ulv']);
            $this->name = $myinfo['uname'];
            
            $stagestr = $myequip['pvpstagepartner'];
            if($stagestr){
                $partnerinfo = sql_fetch_rows("select u.partnerid,c.mainp,c.partnerbase,c.upep,c.incAttr1,c.incAttr2,c.incAttr3,c.incAttr4,c.incAttr5,u.pid,u.quality,u.starlv,u.plv from cfg_partner c inner join upartner u on c.partnerid=u.pid where u.uid=$this->uid and u.partnerid in ($stagestr)");
//                 $equips = sql_fetch_rows("select c.*,u.* from cfg_equip c inner join ubag u on c.eid=u.ceid where u.uid=$this->uid and u.euser in ($stagestr)");
                foreach ($partnerinfo as $pinfo){
                	//初始化属性
                	$this->hp=0;
                	$this->atk =0;
                	$this->def=0;
                	$this->mdef=0;
                	$this->cri=0;
                	$this->cure=0;
                	
                    $lv = intval($pinfo['plv']);
                    $maip = intval($pinfo['mainp']);
                    $pid = intval($pinfo['pid']);
                    $quality = intval($pinfo['quality']);
                    $starlv = intval($pinfo['starlv']);
                    $nowbaseattr=preg_split("/[\s;]+/", $pinfo['partnerbase']);//
                    $baseattr = preg_split("/[\s,]+/", $nowbaseattr[$quality]);
                    
                    
                    $atkbase =0;
                    $hpbase =0;
                    $defbase =0;
                    $mdefbase =0;
                    $cribase =0;
                    $curebase =0;
                    
                    //给基础属性赋值
                    if (count($baseattr) > 0) {
                    	foreach ($baseattr as $a) {
                    		$detail = preg_split("/[\s|]+/", $a);
                    		if (count($detail) == 2) {
                    			switch (intval($detail[0])) {
                    				case 1:
                    					$atkbase = floatval($detail[1]);
                    					break;
                    				case 2:
                    					$hpbase = floatval($detail[1]);
                    					break;
                    				case 3:
                    					$defbase = floatval($detail[1]);
                    					break;
                    				case 4:
                    					$mdefbase = floatval($detail[1]);
                    					break;
                    				case 5:
                    					$cribase = floatval($detail[1]);
                    					break;
                    				case 6:
                    					$curebase = floatval($detail[1]);
                    					break;
                    				default:
                    					break;
                    			}
                    		}
                    	}
                    }

                              
                    if($quality == 0){
                    	$parr = preg_split("/[\s,]+/", $pinfo['upep']);
                    }
                    else{
                    	$parr = preg_split("/[\s,]+/",$pinfo['incAttr' . $quality]);
                    }
                    
                    //取出勇者等级阶段
                    $lvlimit=intval(sql_fetch_one_cell("select uplv from cfg_zhuanzhiunlock where id=$quality"));
                    $atk=0;
                    $hp=0;
                    $def=0;
                    $mdef=0;
                    $cri=0;
                    $cure=0;
                    //     return $parr;
                    //     return array($atkbase,$hpbase,$defbase,$mdefbase,$cribase,$curebase);
                    //计算进阶次数的升级奖励
                    if (count($parr) > 0 ) {
                    	foreach ($parr as $p) {
                    		$pdetail = preg_split("/[\s|]+/", $p);
                    		if (count($pdetail) == 2) {
                    			switch (intval($pdetail[0])) {
                    				case 1: //(基础攻击力+(等级攻击力系数)*(当前等级- 当前转职阶级对应的限制等级) )*1.05的进阶等级次方;
                    					$atk=(ceil((($atkbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                    					break;
                    				case 2: // 敏捷：（等级-1）*（敏捷系数*7+初始敏捷）*（1+0.01*进阶等级）
                    					$hp=(ceil((($hpbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                    					break;
                    				case 3: // 智力：（等级-1）*（智力系数*7+初始智力）*（1+0.01*进阶等级）
                    					$def=(ceil((($defbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                    					break;
                    				case 4: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
                    					$mdef=(ceil((($mdefbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                    					break;
                    				case 5: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
                    					$cri=(ceil((($cribase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                    					break;
                    				case 6: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
                    					$cure=(ceil((($curebase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                    					break;
                    				default:
                    					break;
                    			}
                    		}
                    	}
                    }
                    
                    $this->hp += $hp;
                    $this->atk += $atk;
                    $this->def += $def;
                    $this->mdef += $mdef;
                    $this->cri += $cri;
                    $this->cure +=$cure;
                    $uid = intval($myinfo['uid']);
                    $partnerid = intval($pinfo['partnerid']);
                  	$equips = sql_fetch_rows("select c.*,u.* from cfg_equip c inner join ubag u on c.eid=u.ceid where u.uid=$uid and u.euser = $partnerid");
                    //=========================================================
                    //计算装备属性
                    $e_hp = 0;
                    $e_patk = 0;
                    $e_matk = 0;
                    $e_pdef = 0;
                    $e_mdef = 0;
                    $e_cri = 0;
                    $e_cure = 0;
                    $suits = array();
                    $stardate=array(3=>0.05,4=>0.05,5=>0.05);         
                    foreach($equips as $equip){
//                     	if(intval($equip['suit']) > 0){
//                     		$suits[] = intval($equip['suit']);
//                     	}
                    	$uplv = intval($equip['uplv']);
                    	$star = intval($equip['star']);
                    	$xishu=$stardate[$star];
                    	$e_hp += ceil(intval($equip['hp']) * (1+$xishu*$uplv));
                    	$e_patk += ceil(intval($equip['patk']) * (1+$xishu*$uplv));
                    	$e_matk += ceil(intval($equip['matk']) * (1+$xishu*$uplv));
                    	$e_pdef += ceil(intval($equip['pdef']) * (1+$xishu*$uplv));
                    	$e_mdef += ceil(intval($equip['mdef']) * (1+$xishu*$uplv));
                    	$e_cri += ceil(intval($equip['cri']) * (1+$xishu*$uplv));
                    	$e_cure += ceil(intval($equip['cure']) * (1+$xishu*$uplv));
                    }
//                     $suits = array_count_values($suits);              
                    $this->hp += $e_hp;
                    $this->atk += $e_patk;
                    $this->def += $e_pdef;
                    $this->mdef += $e_mdef;
                    $this->cri += $e_cri;
                    $this->cure += $e_cure;
                    // 战力 = (HP*0.29+攻击力*1.9+物理防御*0.19+魔法防御*0.19+暴击*0.24+韧性*0.28+主属性*1.5
                    $pjob=intval($pinfo['mainp']);
                    $jobnum=floatval(sql_fetch_one_cell("select num from cfg_jobdate where jid=$pjob"));
                    //$this->zhanliar[]=ceil(($this->hp * 0.29 + $this->atk * 1.9 + $this->def * 0.19 + $this->mdef * 0.19 + $this->cri * 0.24 + $this->cure * 0.28)/3*$jobnum);
                    $this->zhanli+= ceil(($this->hp * 0.29 + $this->atk * 1.9 + $this->def * 0.19 + $this->mdef * 0.19 + $this->cri * 0.24 + $this->cure * 0.28)/3*$jobnum);
                }
               
                
            }
        }
        elseif($t == 2){
            $this->uid = $myinfo['uid'];
            $this->lv = intval($myinfo['ulv']);
            $this->name = $myinfo['uname'];
        
            if($myequip){
                $partnerinfo = sql_fetch_rows("select u.partnerid,c.mainp,c.partnerbase,c.upep,c.incAttr1,c.incAttr2,c.incAttr3,c.incAttr4,c.incAttr5,u.pid,u.quality,u.starlv,u.plv from cfg_partner c inner join upartner u on c.partnerid=u.pid where u.uid=$this->uid and u.partnerid in ($myequip)");
                foreach ($partnerinfo as $pinfo){
                    //初始化属性
                    $this->hp=0;
                    $this->atk =0;
                    $this->def=0;
                    $this->mdef=0;
                    $this->cri=0;
                    $this->cure=0;

                    $lv = intval($pinfo['plv']);
                    $maip = intval($pinfo['mainp']);
                    $pid = intval($pinfo['pid']);
                    $quality = intval($pinfo['quality']);
                    $starlv = intval($pinfo['starlv']);
                    $nowbaseattr=preg_split("/[\s;]+/", $pinfo['partnerbase']);//
                    $baseattr = preg_split("/[\s,]+/", $nowbaseattr[$quality]);

                    $atkbase =0;
                    $hpbase =0;
                    $defbase =0;
                    $mdefbase =0;
                    $cribase =0;
                    $curebase =0;
        
                    //给基础属性赋值
                    if (count($baseattr) > 0) {
                        foreach ($baseattr as $a) {
                            $detail = preg_split("/[\s|]+/", $a);
                            if (count($detail) == 2) {
                                switch (intval($detail[0])) {
                                    case 1:
                                        $atkbase = floatval($detail[1]);
                                        break;
                                    case 2:
                                        $hpbase = floatval($detail[1]);
                                        break;
                                    case 3:
                                        $defbase = floatval($detail[1]);
                                        break;
                                    case 4:
                                        $mdefbase = floatval($detail[1]);
                                        break;
                                    case 5:
                                        $cribase = floatval($detail[1]);
                                        break;
                                    case 6:
                                        $curebase = floatval($detail[1]);
                                        break;
                                    default:
                                        break;
                                }
                            }
                        }
                    }

                    if($quality == 0){
                        $parr = preg_split("/[\s,]+/", $pinfo['upep']);
                    }
                    else{
                        $parr = preg_split("/[\s,]+/",$pinfo['incAttr' . $quality]);
                    }
        
                    //取出勇者等级阶段
                    $lvlimit=intval(sql_fetch_one_cell("select uplv from cfg_zhuanzhiunlock where id=$quality"));
                    $atk=0;
                    $hp=0;
                    $def=0;
                    $mdef=0;
                    $cri=0;
                    $cure=0;
                    //计算进阶次数的升级奖励
                    if (count($parr) > 0 ) {
                        foreach ($parr as $p) {
                            $pdetail = preg_split("/[\s|]+/", $p);
                            if (count($pdetail) == 2) {
                                switch (intval($pdetail[0])) {
                                    case 1: //(基础攻击力+(等级攻击力系数)*(当前等级- 当前转职阶级对应的限制等级) )*1.05的进阶等级次方;
                                        $atk=(ceil((($atkbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                                        break;
                                    case 2: // 敏捷：（等级-1）*（敏捷系数*7+初始敏捷）*（1+0.01*进阶等级）
                                        $hp=(ceil((($hpbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                                        break;
                                    case 3: // 智力：（等级-1）*（智力系数*7+初始智力）*（1+0.01*进阶等级）
                                        $def=(ceil((($defbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                                        break;
                                    case 4: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
                                        $mdef=(ceil((($mdefbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                                        break;
                                    case 5: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
                                        $cri=(ceil((($cribase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                                        break;
                                    case 6: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
                                        $cure=(ceil((($curebase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                                        break;
                                    default:
                                        break;
                                }
                            }
                        }
                    }
        
                    $this->hp += $hp;
                    $this->atk += $atk;
                    $this->def += $def;
                    $this->mdef += $mdef;
                    $this->cri += $cri;
                    $this->cure +=$cure;
                    $uid = intval($myinfo['uid']);
                    $partnerid = intval($pinfo['partnerid']);
                    $equips = sql_fetch_rows("select c.*,u.* from cfg_equip c inner join ubag u on c.eid=u.ceid where u.uid=$uid and u.euser = $partnerid");
                    //=========================================================
                    //计算装备属性
                    $e_hp = 0;
                    $e_patk = 0;
                    $e_matk = 0;
                    $e_pdef = 0;
                    $e_mdef = 0;
                    $e_cri = 0;
                    $e_cure = 0;
                    $suits = array();
                   $stardate=array(3=>0.05,4=>0.05,5=>0.05); 
                    foreach($equips as $equip){
                        if(intval($equip['suit']) > 0){
                            $suits[] = intval($equip['suit']);
                        }
                        $uplv = intval($equip['uplv']);
                        $star = intval($equip['star']);
                        $xishu=$stardate[$star];
                        $e_hp += ceil(intval($equip['hp']) * (1+$xishu*$uplv));
                        $e_patk += ceil(intval($equip['patk']) * (1+$xishu*$uplv));
                        $e_matk += ceil(intval($equip['matk']) * (1+$xishu*$uplv));
                        $e_pdef += ceil(intval($equip['pdef']) * (1+$xishu*$uplv));
                        $e_mdef += ceil(intval($equip['mdef']) * (1+$xishu*$uplv));
                        $e_cri += ceil(intval($equip['cri']) * (1+$xishu*$uplv));
                        $e_cure += ceil(intval($equip['cure']) * (1+$xishu*$uplv));
                    }
                    $suits = array_count_values($suits);
                    $this->hp += $e_hp;
                    $this->atk += $e_patk;
                    $this->def += $e_pdef;
                    $this->mdef += $e_mdef;
                    $this->cri += $e_cri;
                    $this->cure += $e_cure;
                    // 战力 = (HP*0.29+攻击力*1.9+物理防御*0.19+魔法防御*0.19+暴击*0.24+韧性*0.28+主属性*1.5
                    $pjob=intval($pinfo['mainp']);
                    $jobnum=floatval(sql_fetch_one_cell("select num from cfg_jobdate where jid=$pjob"));
                    $this->zhanli += ceil(($this->hp * 0.29 + $this->atk * 1.9 + $this->def * 0.19 + $this->mdef * 0.19 + $this->cri * 0.24 + $this->cure * 0.28)/3*$jobnum);
                }

            }
        }
        elseif($t == 4){
        	//$this->uid = intval($myinfo['partnerid']);
        	//$this->lv = intval($myinfo['plv']);
        	//$this->name = $myinfo['name'];
            $partnerid = intval($myinfo['partnerid']);
            $pid = intval($myinfo['pid']);
            $maip = intval($myinfo['mainp']);
            $uid = intval($myinfo['uid']);
            $lv = intval($myinfo['plv']);
            $pid = intval($myinfo['pid']);
            $quality = intval($myinfo['quality']);
            $starlv = intval($myinfo['starlv']);
            $partnercfg = sql_fetch_one("select * from cfg_partner where partnerid = $pid");
            $equips = sql_fetch_rows("select c.*,u.* from cfg_equip c inner join ubag u on c.eid=u.ceid where u.uid=$uid and u.euser = $partnerid");
            $nowbaseattr=preg_split("/[\s;]+/", $partnercfg['partnerbase']);//
            $baseattr = preg_split("/[\s,]+/", $nowbaseattr[$quality]);
            
            
            $atkbase =0;
            $hpbase =0;
            $defbase =0;
            $mdefbase =0;
            $cribase =0;
            $curebase =0;
            
            //给基础属性赋值
            if (count($baseattr) > 0) {
            	foreach ($baseattr as $a) {
            		$detail = preg_split("/[\s|]+/", $a);
            		if (count($detail) == 2) {
            			switch (intval($detail[0])) {
            				case 1:
            					$atkbase = floatval($detail[1]);
            					break;
            				case 2:
            					$hpbase = floatval($detail[1]);
            					break;
            				case 3:
            					$defbase = floatval($detail[1]);
            					break;
            				case 4:
            					$mdefbase = floatval($detail[1]);
            					break;
            				case 5:
            					$cribase = floatval($detail[1]);
            					break;
            				case 6:
            					$curebase = floatval($detail[1]);
            					break;
            				default:
            					break;
            			}
            		}
            	}
            }
            $this->baseatk=$atkbase;
            $this->basehp=$hpbase;
            $this->basedef=$defbase;
            $this->basemdef=$mdefbase;
            $this->basecri=$cribase;
            $this->basecure=$curebase;
            if($quality == 0){
            	$parr = preg_split("/[\s,]+/", $partnercfg['upep']);
            }
            else{
            	$parr = preg_split("/[\s,]+/",$partnercfg['incAttr' . $quality]);
            }
            
            //取出勇者等级阶段
            $lvlimit=intval(sql_fetch_one_cell("select uplv from cfg_zhuanzhiunlock where id=$quality"));
            $atk=0;
            $hp=0;
            $def=0;
            $mdef=0;
            $cri=0;
            $cure=0;
            //     return $parr;
            //     return array($atkbase,$hpbase,$defbase,$mdefbase,$cribase,$curebase);
            //计算进阶次数的升级奖励
            if (count($parr) > 0 ) {
            	foreach ($parr as $p) {
            		$pdetail = preg_split("/[\s|]+/", $p);
            		if (count($pdetail) == 2) {
            			switch (intval($pdetail[0])) {
            				case 1: //(基础攻击力+(等级攻击力系数)*(当前等级- 当前转职阶级对应的限制等级) )*1.05的进阶等级次方;
            					$atk=(ceil((($atkbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
            					break;
            				case 2: // 敏捷：（等级-1）*（敏捷系数*7+初始敏捷）*（1+0.01*进阶等级）
            					$hp=(ceil((($hpbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
            					break;
            				case 3: // 智力：（等级-1）*（智力系数*7+初始智力）*（1+0.01*进阶等级）
            					$def=(ceil((($defbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
            					break;
            				case 4: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
            					$mdef=(ceil((($mdefbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
            					break;
            				case 5: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
            					$cri=(ceil((($cribase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
            					break;
            				case 6: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
            					$cure=(ceil((($curebase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
            					break;
            				default:
            					break;
            			}
            		}
            	}
            }
            $this->hp += $hp;
            $this->atk += $atk;
            $this->def += $def;
            $this->mdef += $mdef;
            $this->cri += $cri;
            $this->cure += $cure;
            //=========================================================
            //计算装备属性
                    $stardate=array(3=>0.05,4=>0.05,5=>0.05);        
                    foreach($equips as $equip){
                    	if(intval($equip['suit']) > 0){
                    		$suits[] = intval($equip['suit']);
                    	}
                    	$uplv = intval($equip['uplv']);
                    	$star = intval($equip['star']);
                    	$xishu=$stardate[$star];
                    	$e_hp += ceil(intval($equip['hp']) * (1+$xishu*$uplv));
                    	$e_patk += ceil(intval($equip['patk']) * (1+$xishu*$uplv));
                    	$e_matk += ceil(intval($equip['matk']) * (1+$xishu*$uplv));
                    	$e_pdef += ceil(intval($equip['pdef']) * (1+$xishu*$uplv));
                    	$e_mdef += ceil(intval($equip['mdef']) * (1+$xishu*$uplv));
                    	$e_cri += ceil(intval($equip['cri']) * (1+$xishu*$uplv));
                    	$e_cure += ceil(intval($equip['cure']) * (1+$xishu*$uplv));
                    }
            $suits = array_count_values($suits);
//             foreach($suits as $key => $value){
//                 if($value >= 2){
//                     $cfgsuit = sql_fetch_one("select * from cfg_equipsuit where sid = $key");
//                     if($cfgsuit){
//                         if($value >= 2 && $value < 4){
//                             if(intval($cfgsuit['twosuitshuxing']) == 5){
//                                 $e_patk = ceil($e_patk * (1 + intval($cfgsuit['twosuit'])/10000));
//                             }
//                             elseif(intval($cfgsuit['twosuitshuxing']) == 6){
//                                 $e_pdef = ceil($e_pdef * (1 + intval($cfgsuit['twosuit'])/10000));
//                             }
//                             elseif(intval($cfgsuit['twosuitshuxing']) == 7){
//                                 $e_cure = ceil($e_cure * (1 + intval($cfgsuit['twosuit'])/10000));
//                             }
//                             elseif(intval($cfgsuit['twosuitshuxing']) == 8){
//                                 $e_mdef = ceil($e_mdef * (1 + intval($cfgsuit['twosuit'])/10000));
//                             }
//                             elseif(intval($cfgsuit['twosuitshuxing']) == 10){
//                                 $e_hp = ceil($e_hp * (1 + intval($cfgsuit['twosuit'])/10000));
//                             }
//                             elseif(intval($cfgsuit['twosuitshuxing']) == 11){
//                                 $e_cri = ceil($e_cri * (1 + intval($cfgsuit['twosuit'])/10000));
//                             }
//                         }
//                         elseif($value >= 4 && $value < 6){
//                             if(intval($cfgsuit['foursuitshuxing']) == 5){
//                                 $e_patk = ceil($e_patk * (1 + intval($cfgsuit['foursuit'])/10000));
//                             }
//                             elseif(intval($cfgsuit['foursuitshuxing']) == 6){
//                                 $e_pdef = ceil($e_pdef * (1 + intval($cfgsuit['foursuit'])/10000));
//                             }
//                             elseif(intval($cfgsuit['foursuitshuxing']) == 7){
//                                 $e_cure = ceil($e_cure * (1 + intval($cfgsuit['foursuit'])/10000));
//                             }
//                             elseif(intval($cfgsuit['foursuitshuxing']) == 8){
//                                 $e_mdef = ceil($e_mdef * (1 + intval($cfgsuit['foursuit'])/10000));
//                             }
//                             elseif(intval($cfgsuit['foursuitshuxing']) == 10){
//                                 $e_hp = ceil($e_hp * (1 + intval($cfgsuit['foursuit'])/10000));
//                             }
//                             elseif(intval($cfgsuit['foursuitshuxing']) == 11){
//                                 $e_cri = ceil($e_cri * (1 + intval($cfgsuit['foursuit'])/10000));
//                             }
//                         }
//                         elseif($value >= 6){
//                             if(intval($cfgsuit['sixsuitshuxing']) == 5){
//                                 $e_patk = ceil($e_patk * (1 + intval($cfgsuit['sixsuit'])/10000));
//                             }
//                             elseif(intval($cfgsuit['sixsuitshuxing']) == 6){
//                                 $e_pdef = ceil($e_pdef * (1 + intval($cfgsuit['sixsuit'])/10000));
//                             }
//                             elseif(intval($cfgsuit['sixsuitshuxing']) == 7){
//                                 $e_cure = ceil($e_cure * (1 + intval($cfgsuit['sixsuit'])/10000));
//                             }
//                             elseif(intval($cfgsuit['sixsuitshuxing']) == 8){
//                                 $e_mdef = ceil($e_mdef * (1 + intval($cfgsuit['sixsuit'])/10000));
//                             }
//                             elseif(intval($cfgsuit['sixsuitshuxing']) == 10){
//                                 $e_hp = ceil($e_hp * (1 + intval($cfgsuit['sixsuit'])/10000));
//                             }
//                             elseif(intval($cfgsuit['sixsuitshuxing']) == 11){
//                                 $e_cri = ceil($e_cri * (1 + intval($cfgsuit['sixsuit'])/10000));
//                             }
//                         }
//                     }
//                 }
//             }
            $this->hp += $e_hp;
            $this->atk += $e_patk;
            $this->def += $e_pdef;
            $this->mdef += $e_mdef;
            $this->cri += $e_cri;
            $this->cure += $e_cure;
            // 战力 = (HP*0.29+攻击力*1.9+物理防御*0.19+魔法防御*0.19+暴击*0.24+韧性*0.28)/3*职业战力系数
            $pjob=intval($partnercfg['mainp']);
            $jobnum=floatval(sql_fetch_one_cell("select num from cfg_jobdate where jid=$pjob"));
            $this->zhanli = ceil(($this->hp * 0.29 + $this->atk * 1.9 + $this->def * 0.19 + $this->mdef * 0.19 + $this->cri * 0.24 + $this->cure * 0.28)/3*$jobnum);
           // echo "===="."zhanli:"."$this->zhanli"."hp:"."$this->hp"."atk:"."$this->atk"."def:"."$this->def"."mdef:"."$this->mdef"."cri:"."$this->cri"."cure:"."$this->cure"."mainv:"."$this->mainv"."====";
        }

    }

    public function format_to_array()
    {
        $fields1 = array(
            // 总属性
            'zhanli' => 'zhanli', // 战力
            // 基础属性
//             'basestr' => 'liliang', // 力量
//             'basedex' => 'minjie', // 敏捷
//             'baseint' => 'zhili', // 智力
//             'basevit' => 'tili', // 体力
            'hp' => 'hp', // 生命值
            // 战斗属性
            'atk' => 'gongji', // 攻击力
            'def' => 'wufang', // 物防
            'mdef' => 'mofang', // 魔防
            'cri' => 'baoji', // 暴击
            'cure' => 'zhiliao', // 治疗
        );
        $res = array();
        foreach ($fields1 as $key1 => $key2) {
            $res[$key2] = $this->$key1;
        }
        return array(
            $res
        );
    }

    public function format_to_array2()
    {
        $fields1 = array(
            // 总属性
            'baseatk' => 'baseatk',// 攻击力
            'basehp' => 'basehp' ,// 生命值
            'basedef' => 'basedef', // 物理防御
            'basemdef' => 'basemdef', // 魔法防御
            'basecri' => 'basecri', // 暴击
            'basecure' => 'basecure' // 韧性
        );
        $res = array();
        foreach ($fields1 as $key1 => $key2) {
            $res[$key2] = $this->$key1;
        }
        return array(
            $res
        );
    }

}

?>