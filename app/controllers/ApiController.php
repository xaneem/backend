<?php

class ApiController extends BaseController {


	public function index()
	{
		return Response::json(array(
			'status'=>'active',
			'contacts' => array(
				'Cultural Secretary' => array('Abdul Wasih', '+91-1122334455', 'wasih@ragam.org.in'),
				'Somebody' => array('Someone', '+91-1234567890', 'someone@ragam.org.in'),
				),

			'updates' => array(
				'This is the latest message',
				'This is somewhat new',
				'This is the oldest'
				),

			));

	}


	public function events(){
		//Get base category
		$categories = EventCategories::where('parent_id','=',0)->get();

		$categories->map(function($category){
			//get childrens
			$sub = EventCategories::where('parent_id', '=', $category->id)->get();

			if($sub->count()>0)
				$category->sub_categories = $sub;

			$sub->map(function($sub_cat){
				$events = Events::where('category_id','=',$sub_cat->id)->whereValidated(true)->get(['event_code', 'name', 'tags', 'prizes', 'short_description', 'team_min', 'team_max']);

				if($events->count()>0)
					$sub_cat->events = $events;

				return $sub_cat;
			});

			$events = Events::where('category_id','=',$category->id)->whereValidated(true)->get(['event_code', 'name', 'tags', 'prizes', 'short_description', 'team_min', 'team_max']);

			if($events->count()>0)
				$category->events = $events;


			return $category;

		});

		return $categories;
	}


	public function event($code){
		$event = Events::where('event_code','=',$code)->get();

		if($event->count() == 0)
			return Response::json(['response'=>'error','reason'=>'no_event']);

		return $event;
	}


	public function user(){
		if(Auth::user()->check()){
			$id =  Auth::user()->get()->id;
		}else{
			if(Request::ajax())
				return Response::json(['result'=>'fail','reason'=>'not_logged_in']);
		    
		    return Redirect::to(Config::get('app.homepage'));
		}

		return Registration::whereId($id)->with('college')->get(['email','name','phone','runtime_id','college_id']);
	}

	public function userPostLogin(){
		$email = Input::get('email');
		$password = Input::get('password');

		if(Auth::user()->attempt(array('email' => $email, 'password' => $password)))
		{
			if(Request::ajax())
				return Response::json(['result'=>'success']);
		    
		    return Redirect::intended(Config::get('app.homepage'));
		}
	}

	public function userLogout(){
		Auth::user()->logout();

		if(Request::ajax())
			return Response::json(['result'=>'success']);

		return Redirect::to(Config::get('app.homepage'));		
	}


	public function userFbLogin(){

		$code = Input::get('code');
		$fb = OAuth::consumer('Facebook');

		//If code is not empty, try to get details.
		if (!empty($code)) {

			try {
				$token = $fb->requestAccessToken($code);		
			} catch (Exception $e) {

				if (Request::ajax())
					return Response::json(['result'=>'fail','reason'=>'fb_exception']);
				
				return Redirect::intended(Config::get('app.homepage'));	
			}

			$result = json_decode( $fb->request( '/me' ), true);

			//Make sure we have the intended results.
			if(is_array($result) && array_key_exists('id', $result)){
				$user = Registration::where('fb_uid', '=', $result['id'])->get();

				if($user->count() == 0){
					//Check if email has been registered.
					if(array_key_exists('email', $result)){
						$user = Registration::where('email','=',$result['email'])->get();

						if($user->count() > 0){
							$user = $user->first();
							$user->fb_uid = $result['id'];

							if($user->name == '')
								$user->name = $result['first_name'].' '.$result['last_name'];

							$user->save();

							Auth::user()->login($user);

							if(Request::ajax())
								return Response::json(['result'=>'success']);
							return Redirect::intended(Config::get('app.homepage'));
						}
					}

					//In case user has not registered before OR if FB doesn't provide email
					$user = new Registration;
					$user->fb_uid = $result['id'];
					$user->email = $result['email'];
					$user->name = $result['first_name'].' '.$result['last_name'];
					$user->save();

					Auth::user()->login($user);

					if(Request::ajax())
						return Response::json(['result'=>'success']);

					return Redirect::intended(Config::get('app.homepage'));
				}else{
					//User has already logged in with FB before.
					Auth::user()->login($user->first());


					if(Request::ajax())
						return Response::json(['result'=>'success']);

					return Redirect::intended(Config::get('app.homepage'));	
				}
			}else{
				//Some error occured and the result is not retrieved.
				if(Request::ajax())
					return Response::json(['result'=>'fail','reason'=>'no_result']);

				return Redirect::intended(Config::get('app.homepage'));				
			}
		}else{
			$url = $fb->getAuthorizationUri();


			if(Request::ajax())
				return Response::json(['result'=>'fail','reason'=>'requires_redirect','url'=>(string)$url]);

			return Redirect::to( (string)$url );
		}

	}



}