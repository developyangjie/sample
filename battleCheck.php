<?php
function _battleCheck($uid, $params) {
	$type=$params[2];
	switch ($type) {
		//pve验证
		case 1 :
			// 怪物血量
			$mapid = intval ( $params [1] );
			$monster_arr = sql_fetch_one ( "select monsters1,monsters2,monsters3,monsters4,monsters5,monsters6,monsters7 from cfg_pvemap where id=$mapid " );
			$monsterid_arr = array (); // 最终怪物id数组
			foreach ( $monster_arr as $value ) {
				if ($value != "") {
					$onemst_arr = explode ( ",", $value );
					foreach ( $onemst_arr as $monsterid ) {
						array_push ( $monsterid_arr, $monsterid );
					}
				}
			}
			$monsterid_str = implode ( ",", $monsterid_arr );
			$monster_hparr = sql_fetch_rows ( "select mid,hp from cfg_monster where mid in($monsterid_str)" );
			$i = 1;
			$monster_hp = array ();
			foreach ( $monsterid_arr as $value ) {
				foreach ($monster_hparr as $values)
				{
					if($value==$values['mid'])
					{
					$monster_hp ["$i"]['hp'] = intval($values['hp']);
					$i ++;
					break;
					}
				}
			}
			// 人物血量
			$partner_arr = sql_fetch_one_cell ( "select pos from upve where uid=$uid" );
			$partneridps_arr = explode ( ",", $partner_arr );
			$partnerid_arr = array (); // 最终佣兵id数组
			foreach ( $partneridps_arr as $value ) {
				$partnerid_str = explode ( "|", $value );
				$partnerid = intval ( $partnerid_str [1] );
				// $pts=sql_fetch_one_cell("select pid from upartner where uid=$uid and partnerid=$partnerid ");
				array_push ( $partnerid_arr, $partnerid );
			}
			$i = 1;
			$partner_hp = array ();
			// return $partnerid_arr;
			foreach ( $partnerid_arr as $v ) {
				$partnerAttr = getPartnerAttr ( $uid, intval ( $v ) );
				$partner_hp ["$i"] = $partnerAttr ["$v"] ['addattr_hp'];
				$i ++;
			}
			// return $partner_hp;
			break;
		//pvp验证	
		case 2 :
			// 敌人血量
			$mid_hp=sql_fetch_one_cell("select matchpos from upvpdata where uid=$uid");
			$monster_hparr=explode(",", $mid_hp);
			$i = 1;
			$monster_hp = array ();
			foreach ( $monster_hparr as $value ) {
				$partner_arr=getPartnerAttr($uid, $value);
				$monster_hp ["$i"]['hp'] = $partner_arr['addattr_hp'];
				$i ++;
			}
			// 人物血量
			$partner_arr = sql_fetch_one_cell ( "select pvpstagepartner from uequip where uid=$uid" );
			$partneridps_arr = explode ( ",", $partner_arr );
			$i = 1;
			$partner_hp = array ();
			foreach ( $partneridps_arr as $v ) {
				$partnerAttr = getPartnerAttr ( $uid, intval ( $v ) );
				$partner_hp ["$i"] = $partnerAttr ["$v"] ['addattr_hp'];
				$i ++;
			}

			
			break;
		//club验证	
		case 3 :
			// 怪物血量
			$mstagepartner=$params[1]['stagepartner'];
			$muid=$params[1]['uid'];
			$monteridps_arr = explode ( ",", $mstagepartner );
			$i = 1;
			$monster_hp = array ();
			foreach ( $monteridps_arr as $v ) {
				$monsterAttr = getPartnerAttr ( $muid, intval ( $v ) );
				$monster_hp ["$i"]['hp'] = $monsterAttr ["$v"] ['addattr_hp'];
				$i ++;
			}
			// 人物血量
			$partner_arr = sql_fetch_one_cell ( "select pvsstagepartner from uequip where uid=$uid" );
			$partneridps_arr = explode ( ",", $partner_arr );
			$i = 1;
			$partner_hp = array ();
			foreach ( $partneridps_arr as $v ) {
				$partnerAttr = getPartnerAttr ( $uid, intval ( $v ) );
				$partner_hp ["$i"] = $partnerAttr ["$v"] ['addattr_hp'];
				$i ++;
			}
			
			break;
		case 4 :
				// 怪物血量
				$mapid = intval ( $params [1] );
				$monsterid= sql_fetch_one_cell( "select bossid from cfg_specialmap where id=$mapid " );
// 				$monsterid_arr = array (); // 最终怪物id数组
// 				foreach ( $monster_arr as $value ) {
// 					if ($value != "") {
// 						$onemst_arr = explode ( ",", $value );
// 						foreach ( $onemst_arr as $monsterid ) {
// 							array_push ( $monsterid_arr, $monsterid );
// 						}
// 					}
// 				}
// 				$monsterid_str = implode ( ",", $monsterid_arr );
				$monster_hparr = sql_fetch_rows ( "select mid,hp from cfg_monster where mid=$monsterid" );
				$i = 1;
				$monster_hp = array ();
				foreach ($monster_hparr as $values)
				{
					if($value==$values['mid'])
					{
						$monster_hp ["$i"]['hp'] = intval($values['hp']);
						$i ++;
						break;
					}
				}
				
				// 人物血量
				$partner_arr = sql_fetch_one_cell ( "select pos from uspve where uid=$uid" );
				$partneridps_arr = explode ( ",", $partner_arr );
				$partnerid_arr = array (); // 最终佣兵id数组
				foreach ( $partneridps_arr as $value ) {
					$partnerid_str = explode ( "|", $value );
					$partnerid = intval ( $partnerid_str [1] );
					// $pts=sql_fetch_one_cell("select pid from upartner where uid=$uid and partnerid=$partnerid ");
					array_push ( $partnerid_arr, $partnerid );
				}
				$i = 1;
				$partner_hp = array ();
				// return $partnerid_arr;
				foreach ( $partnerid_arr as $v ) {
					$partnerAttr = getPartnerAttr ( $uid, intval ( $v ) );
					$partner_hp ["$i"] = $partnerAttr ["$v"] ['addattr_hp'];
					$i ++;
				}
				// return $partner_hp;
				break;
	}

	// 拆分成每条信息数据
	$log_arr = explode ( "_", $params [0] );
	$log_mun = 0;
	foreach ( $log_arr as $onelog ) {
		$log_mun ++;
		// 一条数据拆分成每类
		$onelog_arrs = explode ( ":", $onelog );
		// 拆分第一类主动方数组
		$miandata = explode ( "|", $onelog_arrs [0] );
		// 信息数据类型标识
		$mainid = intval ( $miandata [0] );
		if (! in_array ( $mainid, array (
				1,
				2,
				3,
				4,
				10 
		) )) {
			continue;
		}
		
		switch ($mainid) {
			// *******************************普通攻击****************************
			case 1 :
				
				// 去掉主动方
				array_shift ( $onelog_arrs );
				if (! isset ( $onelog_arrs )) {
					continue;
				}
				
				// 遍历所有被击方
				foreach ( $onelog_arrs as $onelog_arr ) {
					// 不存在的话跳过
					if (! isset ( $onelog_arr )) {
						continue;
					}
					
					// 拆分被击方的数据 第一位是被影响类型目前只有类型1有效，第二位是被影响目标编号id，第三位为是否暴击未使用,第四位是被影响的数值，第五位是被影响后的数值
					$data = explode ( "|", $onelog_arr );
					// 只有被影响类型目前只有类型1有效
					if ($data [0] == 1) {
						
						// 根据编号id来找寻被影响的目标
						$type = intval ( substr ( $data [1], 0, 1 ) );
						$id = intval ( substr ( $data [1], - 2 ) );
						
						// 类型1是佣兵，2是怪物
						if ($type == 1) {
							
							$partner_hp ["$id"] -= $data [3];
							if ($partner_hp ["$id"] < 0) {
								$partner_hp ["$id"] = 0;
							}
							// if ($data [4] == 0 && $partner_hp ["$id"] != 0) {
							// return array (
							// 01,
							// $log_mun
							// );
							// }
						} else {
							
							$monster_hp ["$id"] ['hp'] -= $data [3];
							if ($monster_hp ["$id"] ['hp'] < 0) {
								$monster_hp ["$id"] ['hp'] = 0;
							}
							// if ($data [4] == 0 && $monster_hp ["$id"]['hp'] != 0) {
							// return array (
							// 02,
							// $log_mun
							// );
							// }
						}
					}
				}
				break;
			// 技能伤害
			case 2 :
				
				// 去掉主动方
				array_shift ( $onelog_arrs );
				if (! isset ( $onelog_arrs )) {
					continue;
				}
				// 遍历所有被击方
				
				foreach ( $onelog_arrs as $onelog_arr ) {
					// 不存在的话跳过
					if (! isset ( $onelog_arr )) {
						continue;
					}
					// 拆分被击方的数据 第一位是被影响类型目前只有类型1有效，第二位是被影响目标编号id，第三位为是否暴击未使用,第四位是被影响的数值，第五位是被影响后的数值
					$data = explode ( "|", $onelog_arr );
					// 只有被影响类型目前只有类型1有效
					if ($data [0] == 1) {
						// 根据编号id来找寻被影响的目标
						$type = intval ( substr ( $data [1], 0, 1 ) );
						$id = intval ( substr ( $data [1], - 2 ) );
						
						// 类型1是佣兵，2是怪物
						if ($type == 1) {
							
							$partner_hp ["$id"] -= $data [3];
							if ($partner_hp ["$id"] < 0) {
								$partner_hp ["$id"] = 0;
							}
							// if (intval($data [4]) == 0 && $partner_hp ["$id"] != 0) {
							// return array (
							// 03,
							// $log_mun
							// );
							// }
						} else {
							// return $monster_hp ["$id"]['hp'];
							$monster_hp ["$id"] ['hp'] -= $data [3];
							if ($monster_hp ["$id"] ['hp'] < 0) {
								$monster_hp ["$id"] ['hp'] = 0;
							}
							// if (intval($data [4]) == 0 && $monster_hp ["$id"]['hp'] != 0) {
							// return array (
							// 04,
							// $monster_hp ["$id"]['hp'],
							// $data [4],
							// $log_mun
							// );
							// }
						}
					}
				}
				;
				break;
			// 定时类buff伤害 或是因为buff造成的追加伤害等
			case 3 :
				// 去掉主动方
				array_shift ( $onelog_arrs );
				if (! isset ( $onelog_arrs )) {
					continue;
				}
				// 遍历所有被击方
				foreach ( $onelog_arrs as $onelog_arr ) {
					// 不存在的话跳过
					if (! isset ( $onelog_arr )) {
						continue;
					}
					// 拆分被击方的数据 第一位是被影响类型目前只有类型1有效，第二位是被影响目标编号id，第三位为是否暴击未使用,第四位是被影响的数值，第五位是被影响后的数值
					$data = explode ( "|", $onelog_arr );
					// 只有被影响类型目前只有类型1有效
					if ($data [0] == 1) {
						// 根据编号id来找寻被影响的目标
						$type = intval ( substr ( $data [1], 0, 1 ) );
						$id = intval ( substr ( $data [1], - 2 ) );
						
						// 类型1是佣兵，2是怪物
						if ($type == 1) {
							$partner_hp ["$id"] -= $data [3];
							if ($partner_hp ["$id"] < 0) {
								$partner_hp ["$id"] = 0;
							}
							// if (intval($data [4]) == 0 && $partner_hp ["$id"] != 0) {
							// return array (
							// 05
							// );
							// }
						} else {
							$monster_hp ["$id"] ['hp'] -= $data [3];
							if ($monster_hp ["$id"] ['hp'] < 0) {
								$monster_hp ["$id"] ['hp'] = 0;
							}
							// if (intval($data [4]) == 0 && $monster_hp ["$id"]['hp'] != 0) {
							// return array (
							// 06
							// );
							// }
						}
					}
				}
				break;
			// 被动技 因为skilled造成的追加伤害或追加技能
			case 4 :
				// 去掉主动方
				array_shift ( $onelog_arrs );
				if (! isset ( $onelog_arrs )) {
					continue;
				}
				// 遍历所有被击方
				foreach ( $onelog_arrs as $onelog_arr ) {
					// 不存在的话跳过
					if (! isset ( $onelog_arr )) {
						continue;
					}
					// 拆分被击方的数据 第一位是被影响类型目前只有类型1有效，第二位是被影响目标编号id，第三位为是否暴击未使用,第四位是被影响的数值，第五位是被影响后的数值
					$data = explode ( "|", $onelog_arr );
					// 只有被影响类型目前只有类型1有效
					if ($data [0] == 1) {
						// 根据编号id来找寻被影响的目标
						$type = intval ( substr ( $data [1], 0, 1 ) );
						$id = intval ( substr ( $data [1], - 2 ) );
						
						// 类型1是佣兵，2是怪物
						if ($type == 1) {
							$partner_hp ["$id"] -= $data [3];
							if ($partner_hp ["$id"] < 0) {
								$partner_hp ["$id"] = 0;
							}
							// if (intval($data [4]) == 0 && $partner_hp ["$id"] != 0) {
							// return array (
							// 07
							// );
							// }
						} else {
							$monster_hp ["$id"] ['hp'] -= $data [3];
							if ($monster_hp ["$id"] ['hp'] < 0) {
								$monster_hp ["$id"] ['hp'] = 0;
							}
							// if (intval($data [4]) == 0 && $monster_hp ["$id"]['hp'] != 0) {
							// return array (
							// 08
							// );
							// }
						}
					}
				}
				break;
			// 角色死亡信息
			case 10 :
// 				$deathid = intval ( $miandata [1] );
// 				// 根据编号id来找寻被影响的目标
// 				$type = intval ( substr ( $deathid, 0, 1 ) );
// 				$id = intval ( substr ( $deathid, - 1 ) );
				
// 				// 类型1是佣兵，2是怪物
// 				if ($type == 1) {
// 					if (intval ( $partner_hp ["$id"] ) != 0) {
// 						return array (
// 								9,
// 								$id,
// 								$partner_hp ["$id"],
// 								$partner_hp,
// 								$log_mun 
// 						);
// 					}
// 				} else {
// 					if (intval ( $monster_hp ["$id"] ['hp'] ) != 0) {
// 						return array (
// 								5,
// 								$monster_hp ["$id"] ['hp'],
// 								$log_mun 
// 						);
// 					}
// 				}
				break;
		}
	}
	
	// 验证结果
	// 怪物是否全部死亡
	// return array($monster_hp,"222");
	foreach ( $monster_hp as $value ) {
		if (intval ( $value ['hp'] ) != 0) {
			return array (
					11,
					$monster_hp,
					$partner_hp
			);
		}
	}
	// 我方是否有存活的
	$live = 0;
	foreach ( $partner_hp as $value ) {
		if (intval ( $value ) != 0) {
			$live = 1;
		}
	}
	if ($live == 0) {
		return array (
				12,
				$monster_hp,
				$partner_hp
		);
	}
	
	return array (
			1,
			$monster_hp,
			$partner_hp 
	);
}


function _battleCheckkey($params){
	$b_arr=$params[0];
	$c_arr=$params[1];	
	$a_arr=$_SESSION['key_str'];
	$miankey=$_SESSION['miankey'];
	$key_num=rand(0, 9);
	$win=1;
	$mykey=md5(($miankey+1)*$a_arr[$key_num]-$b_arr[$key_num]);
	if($mykey!=$c_arr[$key_num])
	{
		return array(
				0,
				STR_Battle_Verify_Error
		);
	}
	
}




?>