<?php
  
	namespace App\Http\Controllers;
	  
	use DB;
	use App\Cm_rr_close;
	use App\Http\Controllers\Controller;
	use Illuminate\Http\Request;
	use App\Varification;
	use App\Authentication;

	class RRCloseController extends Controller{
	  

	    public function index(){

	    	
			$ev_result = 0;
			$error_code = 200;
			$error_msg = Authentication::decodeToeken();
			$RRClose = array();

			/* Authentication Failed */
			if($error_msg){
				
				$ev_result = 1;
				$error_code = 401;

		    }
		    else{

				$RRClose = DB::select('SELECT rc.id, rc.rid, rb.name, rc.start_time, rc.end_time 
										FROM cm_rr_close rc JOIN cm_rr_base rb ON rc.rid = rb.rid 
										ORDER BY rc.rid');
				
		    }

		    $response = [
			  	'ev_result' => $ev_result,
				'ev_message' => $error_msg,
				'ea_data' => $RRClose
		  	];

		    return response()->json($response, $error_code);

	    }

	    public function getRRInfoClose(){

			$ev_result = 0;
			$error_code = 200;
			$error_msg = Authentication::viewOrEdit();
			$RRClose = array();
			$RRInfoRet = array();

			/* Authentication Failed */
			
			if($error_msg){
				
				$ev_result = 1;
				$error_code = 401;

		    }
		    else{
				
		    	$RRInfo = $this->getRRInfo();

				$nowTime = DB::select('SELECT UNIX_TIMESTAMP((SELECT NOW())) as `now`')[0]->now;
				
				$RRClose = DB::select('SELECT rc.id, rc.rid
										FROM cm_rr_close rc
										WHERE  rc.end_time >= ?
										ORDER BY rc.rid', array($nowTime));

		    	foreach($RRInfo->rrinfo as &$info){
		    		
		    		$info->status = 0;

		    		foreach($RRClose as &$close){

						if($close->rid == $info->rid){
							$info->status = 1;
							
						}

					}
		    	}
		    	$RRInfoRet = $RRInfo->rrinfo;

				
		    }

		    $response = [
			  	'ev_result' => $ev_result,
				'ev_message' => $error_msg,
				'ea_data' => $RRInfoRet
		  	];

		    return response()->json($response, $error_code);

	    }

	   	public function getOneClose($rid){

			$ev_result = 0;
			$error_code = 200;
			$error_msg = Authentication::decodeToeken();
			$RRClose = array();

			/* Authentication Failed */
			if($error_msg){
				
				$ev_result = 1;
				$error_code = 401;

		    }
		    else{
				
				$RRClose = DB::select('SELECT rc.id, rc.rid, rc.start_time, rc.end_time 
										FROM cm_rr_close rc 
										WHERE rc.rid = ?
										ORDER BY rc.rid', array($rid));
				
		    }

		    $response = [
			  	'ev_result' => $ev_result,
				'ev_message' => $error_msg,
				'ea_data' => $RRClose
		  	];

		    return response()->json($response, $error_code);

	    }

	    public function createRRClose(Request $request){

	    	$ev_result = 0;
	    	$error_code = 200;
			$error_msg = Authentication::decodeToeken();

			/* Authentication Failed */
			if($error_msg){

				$ev_result = 1;
				$error_code = 401;

			}

			elseif(sizeof($request->all()) != 3){
					
				$ev_result = 1;
				$error_code = 401;
				$error_msg = 'Number of Invalid amount of arguments';

			}

			elseif(!array_key_exists("rid", $request->all()) || 
					!array_key_exists("start_time", $request->all()) || 
					!array_key_exists("end_time", $request->all())){
				
				$ev_result = 1;
				$error_code = 401;
				$error_msg = 'Field name incorrect';

			}

			elseif(!is_int($request->rid) || 
					!is_string($request->start_time) || 
					!is_string($request->end_time)) {
				
				$ev_result = 1;
				$error_code = 401;
				$error_msg = 'Field type incorrect';
				
			}

			else{
				$temp = array($request->rid, strtotime($request->start_time), strtotime($request->end_time));
				DB::statement('INSERT INTO cm_rr_close(rid, start_time, end_time) 
								VALUES(?, (SELECT FROM_UNIXTIME(?)), (SELECT FROM_UNIXTIME(?)))', $temp);
			}

		    $response = [
			  	'ev_result' => $ev_result,
				'ev_message' => $error_msg,
		  	];

	        return response()->json($response, $error_code);
	  
	    }
	  
	  
	    public function updateRRClose(Request $request){

	    	$ev_result = 0;
	    	$error_code = 200;
			$error_msg = Authentication::decodeToeken();

			/* Authentication Failed */
			if($error_msg){

				$ev_result = 1;
				$error_code = 401;

			}

			elseif(sizeof($request->all()) != 4){
					
				$ev_result = 1;
				$error_code = 401;
				$error_msg = 'Number of Invalid amount of arguments';

			}

			elseif(!array_key_exists("start_time", $request->all()) || 
					!array_key_exists("end_time", $request->all()) || 
					!array_key_exists("id", $request->all()) ||
					!array_key_exists("rid", $request->all())){
				
				$ev_result = 1;
				$error_code = 401;
				$error_msg = 'Field name incorrect';

			}

			elseif(!is_string($request->start_time) || 
					!is_string($request->end_time) || 
					!is_int($request->id) ||
					!is_int($request->rid) ) {
					
				$ev_result = 1;
				$error_code = 401;
				$error_msg = 'Field type incorrect';	

			}

			else{
				DB::statement('UPDATE cm_rr_close
								SET  
								rid = ?,
								start_time = ?,
								end_time = ?
								WHERE id = ?', array_values($request->all()));
			}

	        $response = [
			  	'ev_result' => $ev_result,
				'ev_message' => $error_msg
		  	];

	        return response()->json($response, $error_code);


	    }


	    public function getRRInfo(){

	    	$url = 'https://www.chanmao.ca/index.php?r=MobMonitor/Rrinfo';

	    	$headers = apache_request_headers();
			// Do Error check
			try{
				$jwt = $headers['Authortoken'];
			}
			catch(\Exception $e) {

				try{
					$jwt = $headers['authortoken'];
				}
				catch(\Exception $e) {
					return 'Cannot find header: Authortoken/authortoken';
				}
				
			}
			$ch = curl_init($url);

			curl_setopt_array($ch, array(
			    CURLOPT_RETURNTRANSFER => TRUE,
			    CURLOPT_HTTPHEADER => array(
			        'Authortoken: ' . $jwt,
			        'Content-Type: application/json'
			    )
			));

			// Send the request
			$response = curl_exec($ch);

			// Check for errors
			if($response === FALSE){
			    die(curl_error($ch));
			}

			return json_decode($response);
	    }
	  
	}
?>
