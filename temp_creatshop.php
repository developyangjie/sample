<?php
include_once 'config.php';
include_once 'db.php';


//*****//
//$star 2,3,4 pcount 2,3,4,5,6 buytype 1,2
sql_update("Truncate table cfg_shopbag");
sql_update("insert into cfg_shopbag (eid,uid) values (10000,0)");
$buytype = 1;

$star = 2;
$pcount = 2;

$sql = "insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,temp)
		select '0',ename,etype,ejob,p1,p2,0,0,'',picindex,'$star',elv,eid,'$buytype',lv*30,lv,'$pcount','0' from cfg_equip where ismain=0";
sql_update($sql);
$star = 3;
$pcount = 3;

$sql = "insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,temp)
		select '0',ename,etype,ejob,p1,p2,0,0,'',picindex,'$star',elv,eid,'$buytype',lv*200,lv,'$pcount','0' from cfg_equip where ismain=0";
sql_update($sql);
$star = 4;
$pcount = 4;

$sql = "insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,temp)
		select '0',ename,etype,ejob,p1,p2,0,0,'',picindex,'$star',elv,eid,'$buytype',lv*1000,lv,'$pcount','0' from cfg_equip where ismain=0";
sql_update($sql);
$star = 4;
$pcount = 5;

$sql = "insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,temp)
		select '0',ename,etype,ejob,p1,p2,0,0,'',picindex,'$star',elv,eid,'$buytype',lv*1000,lv,'$pcount','0' from cfg_equip where ismain=0";
sql_update($sql);
$star = 4;
$pcount = 6;

$sql = "insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,temp)
		select '0',ename,etype,ejob,p1,p2,0,0,'',picindex,'$star',elv,eid,'$buytype',lv*1000,lv,'$pcount','0' from cfg_equip where ismain=0";
sql_update($sql);

$buytype = 2;
$star = 2;
$pcount = 2;

$sql = "insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,temp)
		select '0',ename,etype,ejob,p1,p2,0,0,'',picindex,'$star',elv,eid,'$buytype',ceil(50+lv/5),lv,'$pcount','0' from cfg_equip where ismain=0";
sql_update($sql);
$star = 3;
$pcount = 3;

$sql = "insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,temp)
		select '0',ename,etype,ejob,p1,p2,0,0,'',picindex,'$star',elv,eid,'$buytype',ceil(120+lv/2),lv,'$pcount','0' from cfg_equip where ismain=0";
sql_update($sql);
$star = 4;
$pcount = 4;

$sql = "insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,temp)
		select '0',ename,etype,ejob,p1,p2,0,0,'',picindex,'$star',elv,eid,'$buytype',240+lv,lv,'$pcount','0' from cfg_equip where ismain=0";
sql_update($sql);
$star = 4;
$pcount = 5;

$sql = "insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,temp)
		select '0',ename,etype,ejob,p1,p2,0,0,'',picindex,'$star',elv,eid,'$buytype',240+lv,lv,'$pcount','0' from cfg_equip where ismain=0";
sql_update($sql);
$star = 4;
$pcount = 6;

$sql = "insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,temp)
		select '0',ename,etype,ejob,p1,p2,0,0,'',picindex,'$star',elv,eid,'$buytype',240+lv,lv,'$pcount','0' from cfg_equip where ismain=0";
sql_update($sql);

$buytype = 2;
$star = 2;
$pcount = 2;
$sql = "insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,temp)
		select '0',ename,etype,ejob,p1,p2,0,0,'',picindex,'$star',elv,eid,'$buytype',88,lv,'$pcount','0' from cfg_equip where ismain=1 and elv=0";
sql_update($sql);
$star = 3;
$pcount = 3;

$sql = "insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,temp)
		select '0',ename,etype,ejob,p1,p2,0,0,'',picindex,'$star',elv,eid,'$buytype',188,lv,'$pcount','0' from cfg_equip where ismain=1 and elv=0";
sql_update($sql);
$star = 4;
$pcount = 4;
$sql = "insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,temp)
		select '0',ename,etype,ejob,p1,p2,0,0,'',picindex,'$star',elv,eid,'$buytype',368,lv,'$pcount','0' from cfg_equip where ismain=1 and elv=0";
sql_update($sql);

//****多复制几份//
sql_update("insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,ismain,temp) select uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,ismain,temp+1 as temp from cfg_shopbag where temp=0");
sql_update("insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,ismain,temp) select uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,ismain,temp+2 as temp from cfg_shopbag");
sql_update("insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,ismain,temp) select uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,ismain,temp+4 as temp from cfg_shopbag");
sql_update("insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,ismain,temp) select uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,ismain,temp+8 as temp from cfg_shopbag");




//
//for ($j = 0; $j < 1; $j++) {
//	$cfgequips=sql_fetch_rows("select * from cfg_equip where ismain=0");
//	foreach ($cfgequips as $cfgequip)
//	{
//		$equiplv=intval($cfgequip['lv']);
//		$elv=intval($cfgequip['elv']);
//		$ename=$cfgequip['ename'];
//		$ejob=intval($cfgequip['ejob']);
//		$etype=intval($cfgequip['etype']);
//		$p1min=intval($cfgequip['p1min']);
//		$p1max=intval($cfgequip['p1max']);
//		$p1value=rand($p1min, $p1max);
//		$p1=intval($cfgequip['p1']);
//		$p2=intval($cfgequip['p2']);
//		$p2value=0;
//		$gid=$cfgequip['gid'];
//		$gid2=$cfgequip['gid2'];
//		$ceid=intval($cfgequip['eid']);
//		$pstr2="";
//		if ($p2!=0) {
//			$p2min=intval($cfgequip['p2min']);
//			$p2max=intval($cfgequip['p2max']);
//			$p2value=rand($p2min, $p2max);
//		}
//		$pindex=intval($cfgequip['eid']);
//		for ($i = 2; $i < 5; $i++) {
//			$star=$i;
//			$buytype=1;
//			if ($star==2) {
//				$price=$equiplv*30;
//			}
//			elseif ($star==3)
//			{
//				$price=$equiplv*200;
//			}
//			else
//			{
//				$price=$equiplv*1000;
//			}
//			$pcount=$star;
//
//			$sql="insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,temp)
//		values ('0','$ename','$etype','$ejob','$p1','$p2','$p1value','$p2value','$pstr2','$pindex','$star','$elv','$ceid','$buytype','$price','$equiplv','$pcount','$j')";
//			$eid=sql_insert($sql);
//			if ($star==4) {
//				$pcount=$star+1;
//				$sql="insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,temp)
//		values ('0','$ename','$etype','$ejob','$p1','$p2','$p1value','$p2value','$pstr2','$pindex','$star','$elv','$ceid','$buytype','$price','$equiplv','$pcount','$j')";
//				$eid=sql_insert($sql);
//
//				$pcount=$star+2;
//				$sql="insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,temp)
//		values ('0','$ename','$etype','$ejob','$p1','$p2','$p1value','$p2value','$pstr2','$pindex','$star','$elv','$ceid','$buytype','$price','$equiplv','$pcount','$j')";
//				$eid=sql_insert($sql);
//			}
//
//			$buytype=2;
//			if ($star==2) {
//				$price=ceil(50+$equiplv/5);
//			}
//			elseif ($star==3)
//			{
//				$price=ceil(120+$equiplv/2);
//			}
//			else
//			{
//				$price=240+$equiplv;
//			}
//			$pcount=$star;
//			$sql="insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,temp)
//		values ('0','$ename','$etype','$ejob','$p1','$p2','$p1value','$p2value','$pstr2','$pindex','$star','$elv','$ceid','$buytype','$price','$equiplv','$pcount','$j')";
//			$eid=sql_insert($sql);
//			if ($star==4) {
//				$pcount=$star+1;
//				$sql="insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,temp)
//		values ('0','$ename','$etype','$ejob','$p1','$p2','$p1value','$p2value','$pstr2','$pindex','$star','$elv','$ceid','$buytype','$price','$equiplv','$pcount','$j')";
//				$eid=sql_insert($sql);
//
//				$pcount=$star+2;
//				$sql="insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,temp)
//		values ('0','$ename','$etype','$ejob','$p1','$p2','$p1value','$p2value','$pstr2','$pindex','$star','$elv','$ceid','$buytype','$price','$equiplv','$pcount','$j')";
//				$eid=sql_insert($sql);
//			}
//
//		}
//	}
//	$cfgequips=sql_fetch_rows("select * from cfg_equip where ismain=1 and elv=0");
//	foreach ($cfgequips as $cfgequip)
//	{
//		$equiplv=intval($cfgequip['lv']);
//		$elv=intval($cfgequip['elv']);
//		$ename=$cfgequip['ename'];
//		$ejob=intval($cfgequip['ejob']);
//		$etype=intval($cfgequip['etype']);
//		$p1min=intval($cfgequip['p1min']);
//		$p1max=intval($cfgequip['p1max']);
//		$p1value=rand($p1min, $p1max);
//		$p1=intval($cfgequip['p1']);
//		$p2=intval($cfgequip['p2']);
//		$p2value=0;
//		$gid=$cfgequip['gid'];
//		$gid2=$cfgequip['gid2'];
//		$ceid=intval($cfgequip['eid']);
//		$pstr2="";
//		if ($p2!=0) {
//			$p2min=intval($cfgequip['p2min']);
//			$p2max=intval($cfgequip['p2max']);
//			$p2value=rand($p2min, $p2max);
//		}
//		$pindex=intval($cfgequip['eid']);
//		$ismain=1;
//		for ($i = 2; $i < 5; $i++) {
//			$star=$i;
//			$buytype=2;
//			if ($star==2) {
//				$price=88;
//			}
//			elseif ($star==3)
//			{
//				$price=188;
//			}
//			else
//			{
//				$price=368;
//			}
//			$pcount=$star;
//			$sql="insert into cfg_shopbag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,buytype,price,lv,pcount,ismain,temp)
//		values ('0','$ename','$etype','$ejob','$p1','$p2','$p1value','$p2value','$pstr2','$pindex','$star','$elv','$ceid','$buytype','$price','$equiplv','$pcount','$ismain','$j')";
//			$eid=sql_insert($sql);
//		}
//	}
//}


