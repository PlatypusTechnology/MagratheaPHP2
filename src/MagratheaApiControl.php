<?php

namespace Magrathea2;
use Magrathea2\Exceptions\MagratheaApiException;

#######################################################################################
####
####    MAGRATHEA API CONTROL PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Magrathea2 created: 2023-04 by Paulo Martins
####
#######################################################################################

/**
* 
* Control for Create, Read, List, Update, Delete
**/
class MagratheaApiControl {

	protected $model = null;
	protected $service = null;

	public function GetAllHeaders() {
		$headers = [];
		foreach ($_SERVER as $name => $value){
			if (substr($name, 0, 5) == 'HTTP_') {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
		return $headers;
	}

	public function GetAuthorizationToken() {
		$token = $this->GetAllHeaders()["Authorization"];
		$gotToken = false;
		if (substr($token, 0, 6) == 'Basic ') {
			$token = trim(substr($token, 6));
			$gotToken = true;
		}
		if (substr($token, 0, 7) == 'Bearer ') {
			$token = trim(substr($token, 7));
			$gotToken = true;
		}
		if(!$gotToken) {
			$ex = new MagratheaApiException("Invalid Token: [".$token."]", true);
			$ex->SetStatus(401);
			throw $ex;
		}
		return $token;
	}

	public function GetPhpInput() {
		$json = file_get_contents('php://input');
		$jsonData = json_decode($json);
		$data = [];
		if(!$jsonData) return;
		foreach ($jsonData as $key => $value) {
			$data[str_replace('amp;', '', $key)] = $value;
		}
		return $data;
	}

	public function GetPut() {
		if(@$_PUT) return $_PUT;
		if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
			return $this->GetPhpInput();
		}
		return null;
	}
	public function GetPost() {
		if(@$_POST) return $_POST;
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return $this->GetPhpInput();
		}
		return null;
	}

	public function List() {
		try {
			return $this->service->GetAll();
		} catch(\Exception $ex) {
			throw $ex;
		}
	}
	public function Read($params=false) {
		try {
			if(!$params) return $this->List();
			$id = $params["id"];
			return new $this->model($id);
		} catch(\Exception $ex) {
			throw $ex;
		}
	}

	public function Create() {
		$m = new $this->model();
		$data = $this->GetPost();
		if(@$data["id"]) unset($data["id"]);
		foreach ($data as $key => $value) {
			if(property_exists($m, $key)) {
				$m->$key = $value;
			}
		}
		try {
			if($m->Insert()) {
				return $m;
			}
		} catch(\Exception $ex) {
			throw $ex;
		}
	}

	public function Update($params=false) {
		$id = $params["id"];
		$m = new $this->model($id);
		$data = $this->GetPut();

		if(!$data) throw new \Exception("Empty Data Sent", 500);
		foreach ($data as $key => $value) {
			if(property_exists($m, $key)) {
				$m->$key = $value;
			}
		}
		try {
			if($m->Update()) return $m;
		} catch(\Exception $ex) {
			throw $ex;
		}
	}

	public function Delete($params=false) {
		if(!$params) throw new MagratheaApiException("Empty Data Sent", 500);
		$id = $params["id"];
		$m = new $this->model($id);
		try {
			return $m->Delete();
		} catch(\Exception $ex) {
			throw $ex;
		}
	}
}

