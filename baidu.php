<?php
/*
 * baidu class | Version 0.9.0 | Copyright 2014, Cai Cai | Released under the MIT license
 * login、sign、post、zan、meizhi、tdou
 */
class baidu{
	public $useZlib       = FALSE;
	public $returnThis    = FALSE;
	public $lastFetch     = array();
	public $lastReturn    = array();
	public $lastformData = array();
	protected $un         = '';
	protected $uid        = '';
	protected $tbs        = '';
	protected $bduss      = '';
	protected $cookies    = '';
	protected $client     = array();
	protected $formData   = array();
	protected $forumPages = array();
	public function __construct($cookie = NULL,$client = NULL){
		if(!is_null($cookie)){
			$cookie = trim($cookie);
			if(stripos($cookie,'bduss=') === FALSE && stripos($cookie,';') === FALSE){
				$this->bduss = $cookie;
			}elseif(stripos($cookie,'bduss=') !== FALSE && stripos($cookie,';') === FALSE){
				$this->bduss = substr($cookie,6);
			}elseif(preg_match('/bduss\s?=\s?([^ ;]*)/i',$cookie,$matches)){
				$this->bduss = $matches[1];
			}else{
				throw new Exception('请输入合法的cookie',10);
			}
			$this->cookies = 'BAIDUID=' . strtoupper(self::random(32)) . ':FG=1;BDUSS=' . $this->bduss . ';';
		}
		if(is_null($client)) $this->client = self::getClient();
		else $this->client=$client;
	}
	protected function fetch($url,$mobile = TRUE,$usecookie = TRUE){
		$ch = curl_init($url);
		if($mobile === TRUE){
			$common_data = array(
					'from'        => 'baidu_appstore',
					'stErrorNums' => '0',
					'stMethod'    => '1',
					'stMode'      => '1',
					'stSize'      => rand(50,2000),
					'stTime'      => rand(50,500),
					'stTimesNum'  => '0',
					'timestamp'   => time() . self::random(3,TRUE)
			);
			$predata = $this->client + $this->formData + $common_data;
			ksort($predata);
			$this->formData = array();
			if($usecookie === TRUE){
				$this->formData['BDUSS'] = $this->bduss;
			}
			$this->formData += $predata;
			$sign_str = '';
			foreach($this->formData as $key=>$value)
				$sign_str .= $key . '=' . $value;
			$sign = strtoupper(md5($sign_str . 'tiebaclient!!!'));
			$this->formData['sign'] = $sign;
			$http_header = array(
					'User-Agent: BaiduTieba for Android 6.0.1',
					'Content-Type: application/x-www-form-urlencoded',
					'Host: c.tieba.baidu.com',
					'Connection: Keep-Alive'
			);
			if($this->useZlib === TRUE) $http_header[] = 'Accept-Encoding: gzip';
		}else{
			$http_header = array(
					'User-Agent: Mozilla/5.0 (Windows NT 6.3; rv:29.0) Gecko/20100101 Firefox/29.0',
					'Connection: Keep-Alive'
			);
			curl_setopt($ch,CURLOPT_COOKIE,$this->cookies);
		}
		curl_setopt($ch,CURLOPT_HTTPHEADER,$http_header);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
		curl_setopt($ch,CURLOPT_POST,TRUE);
		curl_setopt($ch,CURLOPT_TIMEOUT,10);
		curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($this->formData));
		$res_json = curl_exec($ch);
		curl_close($ch);
		if(empty($res_json)) throw new Exception('网络连接失败',20);
		if($this->useZlib === TRUE) $res_json = gzdecode($res_json);
		$result = @json_decode($res_json,TRUE);
		if($mobile === TRUE){
			if(!array_key_exists('error_code',$result)) throw new Exception('网络连接失败',20);
			if(!empty($result['anti']['tbs'])) $this->tbs = $result['anti']['tbs'];
			if(!empty($result['user']['id']))  $this->uid = $result['user']['id'];
			if(!empty($result['user']['name'])) $this->un = $result['user']['name'];
		}
		$this->last_formData = $this->formData;
		$this->formData      = array();
		$this->lastFetch      = $result;
		return $result;
	}
	public static function simpleFetch($url){
		$ch = curl_init($url);
		curl_setopt($ch,CURLOPT_HTTPHEADER,array(
				'User-Agent: Mozilla/5.0 (Windows NT 6.3; rv:29.0) Gecko/20100101 Firefox/29.0',
				'Connection: Keep-Alive'
		));
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		$content = curl_exec($ch);
		curl_close($ch);
		$content = json_decode($content,true);
		return $content;
	}
	protected function commonReturn($data){
		$result = array();
		if(array_key_exists('no',$data)) $data['error_code'] = $data['no'];
		if(array_key_exists('error',$data)) $data['error_msg'] = $data['error'];
		if($data['error_code'] == 0 && !is_null($data['error_code'])){
			$data['error_msg'] = "执行成功";
		}elseif(!isset($data['error_msg'])){
			$data['error_msg'] = "未知错误,错误代码" . $data['error_code'];
		}else{
			$data['error_msg'] .= " return=" . $data['error_code'];
		}
		$result['status'] = $data['error_code'];
		$result['msg'] = $data['error_msg'];
		if(isset($data['i'])){
			foreach ($data['i'] as $key => $value) {
				$result['data'][$key] = $value;
			}
		}
		$this->lastReturn = $result;
		if($this->returnThis === TRUE) return $this;
		return $result;
	}
	public static function random($length,$numeric = FALSE){
		$seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']),16,$numeric?10:35);
		$seed = $numeric?(str_replace('0','',$seed) . '012340567890'):($seed . 'zZ' . strtoupper($seed));
		$hash = '';
		$max = strlen($seed) - 1;
		for($i = 0 ; $i < $length ; $i++){
			$hash .= $seed{mt_rand(0,$max)};
		}
		return $hash;
	}
	public static function getClient($type = NULL,$model = NULL,$version = NULL){
		$client = array(
				'_client_id'      => 'wappc_138' . self::random(10,TRUE) . '_' . self::random(3,TRUE),
				'_client_type'    => is_null($type)?rand(1,4):$type,
				'_client_version' => is_null($version)?'6.0.1':$version,
				'_phone_imei'     => md5(self::random(16,TRUE)),
				'cuid'            => strtoupper(md5(self::random(16))) . '|' . self::random(15,TRUE),
				'model'           => is_null($model)?'M1':$model
		);
		return $client;
	}
	public static function getRandomContent(){
		$text = <<<EOF
第一次的爱，始终无法轻描淡写。
我对你，只有放弃，没有忘记。
站在心碎的地方，轻轻打一个结，一种缝补，阻止伤痛再流出。
在这个城市，做一道路过的风景，做一次匆匆的过客，只为了一个人。
也许有一天，你回头了，而我却早已，不在那个路口。
EOF;
		$contents = explode("\n",$text);
		$content = $contents[array_rand($contents)];
		return $content;
	}
	public function fetchForumPage($kw){
		$this->formData = array(
				'kw'         => $kw,
				'pn'         => '1',
				'q_type'     => '2',
				'rn'         => '35',
				'scr_dip'    => '1.5',
				'scr_h'      => '800',
				'scr_w'      => '480',
				'st_type'    => 'tb_forumlist',
				'with_group' => '1'
		);
		$result_raw          = $this->fetch('http://c.tieba.baidu.com/c/f/frs/page');
		$forum               = &$this->forumPages[$kw];
		$forum['fid']        = $result_raw['forum']['id'];
		$forum['name']       = $result_raw['forum']['name'];
		$forum['user_level'] = $result_raw['forum']['user_level'];
		$forum['tlist']      = array();
		$tlist_len           = count($result_raw['thread_list']);
		for($i = 0 ; $i < $tlist_len ; $i++){
			$thread             = $result_raw['thread_list'][$i];
			$tlist              = &$forum['tlist'][$i];
			$tlist['tid']       = $thread['id'];
			@$tlist['is_top']   = $thread['is_top'];
			$tlist['is_posted'] = 0;
			if(!empty($thread['first_post_id'])){
				$tlist['pid']      = $thread['first_post_id'];
				$tlist['is_zaned'] = $thread['zan']['is_liked'];
			}
		}
	}
	public function getForumInfo($kw,$type = 'forum'){
		if(!array_key_exists($kw,$this->forumPages)) $this->fetchForumPage($kw);
		$forum = &$this->forumPages[$kw];
		switch($type){
			case 'post':
				$post_threads = array();
				foreach($forum['tlist'] as $thread){
					if($thread['is_top'] == 0 && $thread['is_posted'] == 0) $post_threads[] = $thread;
				}
				$post_thread = $post_threads[array_rand($post_threads)];
				$info = $post_thread['tid'];
				break;
			case 'zan':
				$zan_threads = array();
				foreach($forum['tlist'] as $thread){
					if($thread['is_top'] == 0 && $thread['is_zaned'] == 0) $zan_threads[] = $thread;
				}
				if(!count($zan_threads)) throw new Exception('无可赞的帖子',60);
				$zan_thread  = $zan_threads[array_rand($zan_threads)];
				$info['tid'] = $zan_thread['tid'];
				if( empty($zan_thread['pid'])){
					$temThreadPage     = $this -> fetchThreadPage($info['tid']);
					$zan_thread['pid'] = $temThreadPage['first_pid'];
				}
				if( empty($zan_thread['pid'])){
					throw new Exception('无法取得pid',40);
				}
				$info['pid'] = $zan_thread['pid'];
				break;
			case 'forum':
				$info['fid']        = $forum['fid'];
				$info['name']       = $forum['name'];
				$info['user_level'] = $forum['user_level'];
				break;
			case 'fid':
				$info = $forum['fid'];
		}
		return $info;
	}
	public function fetchThreadPage($tid){
		$this->formData = array(
				'back'       =>'0',
				'kz'         =>$tid,
				'pn'         =>'1',
				'q_type'     =>'2',
				'rn'         =>'30',
				'scr_dip'    =>'1.5',
				'scr_h'      =>'800',
				'scr_w'      =>'480',
				'with_floor' =>'1'
			);
		$result = $this->fetch('http://c.tieba.baidu.com/c/f/pb/page');
		return array(
				'first_pid' => $result['thread']['post_id'] /*一楼的postid*/
			);
	}

	public function fetchClientUserInfo($uid = NULL){
		if(is_null($uid)){
			$temIsOwner = '1';
			$temUid = $this->uid();
		}else{
			$temIsOwner = '0';
			$temUid = $uid;
		}
		$this->formData=array(
				'has_plist'       =>'1',
				'is_owner'        =>$temIsOwner,
				'need_post_count' =>'1',
				'pn'              =>'1',
				'rn'              =>'20',
				'uid'             =>$temUid,
		);
		$result=$this->fetch("http://c.tieba.baidu.com/c/u/user/profile");
		$result['i']=array(
			'sex'            =>$result['user']['sex'],
			'tb_age'         =>$result['user']['tb_age'],
			'fans_num'       =>$result['user']['fans_num'],
			'concern_num'    =>$result['user']['concern_num'],
			'like_forum_num' =>$result['user']['like_forum_num'],
			'intro'          =>$result['user']['intro'],
			'head_photo'     =>'http://tb.himg.baidu.com/sys/portrait/item/'.$result['user']['portrait'],
			'head_photo_h'   =>'http://tb.himg.baidu.com/sys/portrait/item/'.$result['user']['portraith']
		);
		return $this->commonReturn($result);
	}
	public function fetchWebTbs(){
		if(!empty($this->tbs)) return $this->tbs;
		$result = $this->fetch('http://tieba.baidu.com/dc/common/tbs',FALSE);
		if(array_key_exists('is_login',$result) === TRUE && $result['is_login'] === 0) throw new Exception(var_dump($result));
		return $result['tbs'];
	}
	public function clientRelogin(){
		$this->formData = array(
				'bdusstoken' => $this->bduss
		);
		$result = $this->fetch('http://c.tieba.baidu.com/c/s/login');
		if($result['error_code'] != 0) throw new Exception($result['error_msg'],30);
	}
	public function un(){
		if(empty($this->un)) $this->clientRelogin();
		return $this->un;
	}
	public function uid(){
		if(empty($this->uid)) $this->clientRelogin();
		return $this->uid;
	}
	public function tbs(){
		if(empty($this->tbs)) $this->clientRelogin();
		return $this->tbs;
	}
	public function fetchWebUserInfo(){
		$result = $this->fetch('http://tieba.baidu.com/f/user/json_userinfo',FALSE);
		return $result['data'];
	}
	public static function fetchWebUserPanel($un){
		$result = self::simple_fetch('http://tieba.baidu.com/home/get/panel?ie=utf-8&un=' . urlencode($un));
		$data = array();
		if(@count($result['data']['icon_info'])){
			foreach($result['data']['icon_info'] as $icon){
				if($icon['name'] === 'meizhi_level'){
					$data['meizhi'] = array(
							'tid' => $icon['meizhi_thread_id'],
							'fid' => $icon['meizhi_forum_id'],
							'kw'  => $icon['meizhi_forum_name']
					);
				}
			}
		}
		$data = array(
				'uid' => (string)$result['data']['id']
		);
		return $data;
	}
	public function fetchFansList(){
		$result = $this->fetch('http://c.tieba.baidu.com/c/u/fans/page');
		$temHeadPhoto = array ();
		foreach ($result['user_list'] as &$temFans) {
			$temFans['head_photo'] = 'http://tb.himg.baidu.com/sys/portrait/item/'.$temFans['portrait'];
			$temHeadPhoto[] = $temFans['head_photo'];
		}
		$result['head_photo'] = $temHeadPhoto;
		return $result;
	}
	public function fetchFollowList(){
		$result = $this->fetch('http://c.tieba.baidu.com/c/u/follow/page');
		$temHeadPhoto = array ();
		foreach ($result['user_list'] as &$temFans) {
			$temFans['head_photo'] = 'http://tb.himg.baidu.com/sys/portrait/item/'.$temFans['portrait'];
			$temHeadPhoto[] = $temFans['head_photo'];
		}
		$result['head_photo'] = $temHeadPhoto;
		return $result;
	}
	public function login($un,$passwd,$vcode = NULL,$vcode_md5 = NULL){
		$this->formData = array (
				'isphone' => '0',
				'passwd'  => base64_encode($passwd),
				'un'      => $un
		);
		if(!is_null($vcode) && !is_null($vcode_md5)){
			$vcode_data = array(
					'vcode' => $vcode,
					'vcode_md5' => $vcode_md5
			);
			$this->formData += $vcode_data;
		}
		$result = $this->fetch('http://c.tieba.baidu.com/c/s/login',TRUE,FALSE);
		if($result['error_code'] == 0){
			$result['i'] = array(
					"id"    => $result['user']['id'],
					"name"  => $result['user']['name'],
					"bduss" => $result['user']['BDUSS']
			);
		}elseif($result['error_code'] == 5){
			$result['i'] = array(
				'un'            => $un,
				'passwd'        => base64_encode($passwd),
				"need_vcode"    => $result['anti']['need_vcode'],
				"vcode_md5"     => $result['anti']['vcode_md5'],
				"vcode_pic_url" => $result['anti']['vcode_pic_url']
			);
		}
		return $this->commonReturn($result);
	}
	public function sign($kw,$fid = NULL){
		if(is_null($fid)) $fid = $this->getForumInfo($kw,'fid');
		$this->formData = array(
				'fid' => $fid,
				'kw' => $kw,
				'tbs' => $this->tbs()
		);
		$result = $this->fetch('http://c.tieba.baidu.com/c/c/forum/sign');
		return $this->commonReturn($result);
	}
	public function fetchClientLikedForumList(){
		$this->formData = array(
				'like_forum' => '1',
				'recommend' => '0',
				'topic' => '0'
		);
		$result = $this->fetch('http://c.tieba.baidu.com/c/f/forum/forumrecommend');
		return $result['like_forum'];
	}
	public function fetchClientMultisignForumList(){
		$this->formData = array(
				'user_id' => $this->uid()
		);
		$result = $this->fetch('http://c.tieba.baidu.com/c/f/forum/getforumlist');
		return $result['forum_info'];
	}
	public function signMulti(){
		$forums = $this->fetchClientMultisignForumList();
		$forum_ids = '';
		foreach($forums as $forum){
			$forum_ids .= $forum['forum_id'] . ',';
		}		
		$forum_ids = substr($forum_ids,0,-1);
		$this->formData = array(
				'forum_ids' => $forum_ids,
				'tbs' => $this->tbs(),
				'user_id' => $this->uid()
		);
		$result = $this->fetch('http://c.tieba.baidu.com/c/c/forum/msign');

		return $result['info'];
	}
	public function post($kw,$fid = NULL,$tid = NULL,$content = NULL){
		if(is_null($fid)) $fid = $this->getForumInfo($kw,'fid');
		if(is_null($tid)) $tid = $this->getForumInfo($kw,'post');
		if(is_null($content)) $content = self::getRandomContent();
		$this->formData = array(
				'fid'       => $fid,
				'tid'       => $tid,
				'kw'        => $kw,
				'content'   => $content,
				'tbs'       => $this->tbs(),
				'is_ad'     => '0',
				'new_vcode' => '1',
				'anonymous' => '1',
				'vcode_tag' => '11'
		);
		$result = $this->fetch('http://c.tieba.baidu.com/c/c/post/add');
		$result['i'] = array(
				"need_vcode" => $result['info']['need_vcode'],
				"vcode_md5"  => $result['info']['vcode_md5'],
				"vcode_type" => $result['info']['vcode_type']
		);
		return $this->commonReturn($result);
		// (5=>"需要输入验证码"),(7=>"您的操作太频繁了！"),(8=>"您已经被封禁")
	}
	public function zan($kw){
		$data = $this->getForumInfo($kw,'zan');
		$forum = &$this->forumPages[$kw];
		$this->formData = array(
				'action'    => 'like',
				'post_id'   => $data['pid'],
				'st_param'  => 'pb',
				'st_type'   => 'like',
				'thread_id' => $data['tid']
		);
		$result = $this->fetch('http://c.tieba.baidu.com/c/c/zan/like');
		if($result['error_code'] == 0){
			foreach($forum['tlist'] as &$threads){
				if($threads['tid'] == $data['tid']) $threads['is_zaned'] = 1;
			}
		}
		$result['i'] = array(
				'tid' => $data['tid']
		);
		return $this->commonReturn($result);
	}
	public function meizhi($meizhi_un,$votetype = 0,$meizhi_uid = NULL,$meizhi_kw = NULL,$meizhi_fid = NULL){
		$votetype_list = array(
				'meizhi',
				'meizhi',
				'weiniang',
				'renyao'
		);
		if(is_null($meizhi_uid)) $meizhi_uid = self::fetchWebUserPanel($meizhi_un)['uid'];
		$this->formData = array(
				'content'   => '',
				'tbs'       => $this->tbs(),
				'fid'       => $meizhi_fid?$meizhi_fid:'2689814',
				'kw'        => $meizhi_kw?$meizhi_kw:'妹纸',
				'uid'       => $meizhi_uid,
				'scid'      => $this->uid(),
				'vtype'     => $votetype_list[$votetype],
				'ie'        => 'utf-8',
				'vcode'     => '',
				'new_vcode' => '1',
				'tag'       => '11'
		);
		$result = $this->fetch('http://tieba.baidu.com/encourage/post/meizhi/vote',FALSE);
		if($result['no'] == 0){
			$result['data']['level'] = $result['data']['next_level'] - 1;
			$result['i'] = array(
					'meizhi'       => $result['data']['vote_count']['meizhi'],
					'weiniang'     => $result['data']['vote_count']['weiniang'],
					'renyao'       => $result['data']['vote_count']['renyao'],
					'level'        => $result['data']['level'], // 当前认证等级
					'exp_value'    => $result['data']['exp_value'], // 还需经验数
					'levelup_left' => $result['data']['levelup_left']
			);
		}
		return $this->commonReturn($result);
		// 230308 错误原因不明，解决方法不明
		// 2130008 您已经投过了，请过四小时再来投
	}
	public function fetchWebMeizhiPanel($uid){
		$this->formData = array(
				'user_id' => $uid,
				'type' => '1'
		);
		$result = $this->fetch('http://tieba.baidu.com/encourage/get/meizhi/panel',FALSE);
		$forum_name = $result['data']['forum_name'];
		$result['i'] = array(
				'kw'           => $forum_name, // 认证贴吧的吧名
				'meizhi'       => $result['data']['vote_count']['meizhi'],
				'weiniang'     => $result['data']['vote_count']['weiniang'],
				'renyao'       => $result['data']['vote_count']['renyao'],
				'level'        => $result['data']['level'], // 当前认证等级
				'exp_value'    => $result['data']['exp_value'], // 还需经验数
				'levelup_left' => $result['data']['levelup_left'] /*升级还需票数*/
		);
		return $this->commonReturn($result);
	}
	public function getMeizhiStatusString(){
		$result = $this->lastReturn;
		$resultstr = '当前的妹纸票：' . $result['i']['meizhi'] . 
					 '，伪娘票：' . $result['i']['weiniang'] . 
					 '，人妖票：' . $result['i']['renyao'] . 
					 '。<br>认证等级为' . $result['i']['level'] . 
					 '级，再获得' . $result['i']['exp_value'] . 
					 '点经验和' . $result['i']['levelup_left'] . 
					 '张妹纸票后升级。';
		return $resultstr;
	}
	public function tdou(){
		$got_tdou = FALSE;
		$total_score = 0;
		$this->formData = array(
				'ie'  => 'utf-8',
				'tbs' => $this->tbs(),
				'fr'  => 'frs'
		);
		$result = $this->fetch('http://tieba.baidu.com/tbscore/timebeat',FALSE); // 查看状态，是否时间已到
		$retime = $result['data']['time_stat'];
		if($retime['interval_begin_time'] + $retime['time_len'] < $retime['now_time'] &&
		   $retime['time_has_score'] === true ){
			// 如果可以获取时间奖励，就fetch之
			$this->formData = array(
					'ie'  => 'utf-8',
					'tbs' => $this->tbs(),
					'fr'  => 'frs'
			);
			$result = $this->fetch('http://tieba.baidu.com/tbscore/fetchtg',FALSE); // fetchtg=fetch time gift
		}
		$score_info = array(); // 用来存储获取T豆的记录
		if(count($result['data']['gift_info'])){
			foreach($result['data']['gift_info'] as $gift){
				// 取每个gift
				if($gift['gift_type'] == 1) $type = 'time';
				else $type = 'rand';
				$this->formData = array(
						'ie'       => 'utf-8',
						'type'     => $type,
						'tbs'      => $this->tbs(),
						'gift_key' => $gift['gift_key']
				);
				$result = $this->fetch('http://tieba.baidu.com/tbscore/opengift',FALSE);
				$score_info[] = array(
						'gift_type' => $gift['gift_type'],
						'score'     => $result['data']['gift_got']['gift_score']
				);
			}
		}
		if(count($score_info)){
			$got_tdou = TRUE;
			foreach($score_info as $score){
				$total_score += $score['score'];
			}
		}
		$result['i'] = array(
				'time_has_score' => $result['data']['time_stat']['time_has_score'],/* bull 时间奖励是否已经领完 */
				'got_tdou'       => $got_tdou,/* 是否获取到豆票 */
				'total_score'    => $total_score,/* 获取的数目 */
				'score_info'     => $score_info/* 详细信息 */
		);
		return $this->commonReturn($result);
	}
	public function tdouLottery($free = FALSE){
		if($free === FALSE){
			$this->formData = array(
					'kw' => '',
					'tbs' => $this->tbs()
			);
			$result = $this->fetch("http://tieba.baidu.com/tbmall/lottery/tableinfo",FALSE);
			if($result['data']['new_price'] != 0) throw new Exception('免费抽奖机会已经用完',90);
		}
		$this->formData = array(
				'kw' => '',
				'tbs' => $this->tbs()
		);
		$result = $this->fetch("http://tieba.baidu.com/tbmall/lottery/draw",FALSE);
		$result['i'] = array(
				'new_price' => $result['data']['new_price'], // 下一次抽奖所需的T豆
				'win_type'  => $result['data']['award']['win_type'], // 获奖的类型
				'win_id'    => $result['data']['award']['win_id'],
				'win_tips'  => $result['data']['award']['win_tips'] /*奖品信息*/
		);
		return $this->commonReturn($result);
	}
}

