<?php  namespace Jacopo\Authentication\Tests;

use App;
use Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Facade;
use Jacopo\Authentication\Models\User;
use Jacopo\Authentication\Models\UserProfile;
use Jacopo\Library\Exceptions\ValidationException;
use Mockery as m;

/**
 * Test UserControllerTest
 *
 * @author jacopo beschi jacopo@jacopobeschi.com
 */
class UserControllerTest extends DbTestCase
{

    protected $custom_type_repository;
    protected $faker;

    public function setUp()
    {
        parent::setUp();
        $this->faker = \Faker\Factory::create();
        $this->custom_type_repository = App::make('custom_profile_repository');
    }

    public function tearDown()
    {
        m::close();
    }

    /**
     * @test
     **/
    public function it_run_signup_and_return_success_on_post_signup()
    {
        $mock_register = m::mock('StdClass')->shouldReceive('register')->once()->getMock();
        App::instance('register_service', $mock_register);

        $this->action('POST', 'Jacopo\Authentication\Controllers\UserController@postSignup');

        $this->assertRedirectedToAction('Jacopo\Authentication\Controllers\UserController@signupSuccess');

    }

    /**
     * @test
     **/
    public function it_run_signup_and_return_errors_on_post_signup()
    {
        $mock_register = m::mock('StdClass')->shouldReceive('register')->once()->andThrow(new ValidationException())->shouldReceive('getErrors')->once()->getMock();
        App::instance('register_service', $mock_register);

        $this->action('POST', 'Jacopo\Authentication\Controllers\UserController@postSignup');

        $this->assertRedirectedToAction('Jacopo\Authentication\Controllers\UserController@signup');
        $this->assertSessionHasErrors();
    }

    /**
     * @test
     **/
    public function it_show_the_signup_view_on_signup()
    {
        $this->action('GET', 'Jacopo\Authentication\Controllers\UserController@signup');

        $this->assertResponseOk();
    }

    /**
     * @test
     **/
    public function itShowCaptchaOnSignupIfEnabled()
    {
        $this->enableCaptchaCheck();
        $this->action('GET', 'Jacopo\Authentication\Controllers\UserController@signup');

        $this->assertViewHas("captcha");
    }

    /**
     * @test
     **/
    public function itDoesntShowCaptchaOnSignupIfDisabled()
    {
        $this->disableCaptchaCheck();
        $response = $this->action('GET', 'Jacopo\Authentication\Controllers\UserController@signup');

        $this->assertArrayNotHasKey("captcha", $response->original->getData());
    }

    protected function disableCaptchaCheck()
    {
        Config::set('laravel-authentication-acl::captcha_signup', false);
    }

    protected function enableCaptchaCheck()
    {
        Config::set('laravel-authentication-acl::captcha_signup', true);
    }

    /**
     * @test
     **/
    public function it_showConfirmationEmailSuccessOnSignup_ifEmailConfirmationIsEnabled()
    {
        $active = true;
        $this->mockConfigGetEmailConfirmation($active);

        \View::shouldReceive('make')->once()->with('laravel-authentication-acl::client.auth.signup-email-confirmation');

        $this->action('GET', 'Jacopo\Authentication\Controllers\UserController@signupSuccess');
    }

    private function mockConfigGetEmailConfirmation($active)
    {
        Config::set('laravel-authentication-acl::email_confirmation', $active);
    }

    /**
     * @test
     **/
    public function it_showSuccessSignup_ifEmailConfirmationIsDisabled()
    {
        $active = false;
        $this->mockConfigGetEmailConfirmation($active);

        \View::shouldReceive('make')->once()->with('laravel-authentication-acl::client.auth.signup-success');

        $this->action('GET', 'Jacopo\Authentication\Controllers\UserController@signupSuccess');
    }

    /**
     * @test
     **/
    public function it_show_view_with_success_if_token_is_valid()
    {
        $email = "mail";
        $token = "_token";
        $mock_service = m::mock('StdClass')->shouldReceive('checkUserActivationCode')->once()->with($email,
            $token)->getMock();
        App::instance('register_service', $mock_service);

        $this->action('GET', 'Jacopo\Authentication\Controllers\UserController@emailConfirmation',
            '', [
                "email" => $email,
                "token" => $token
            ]);

        $this->assertResponseOk();
    }

    /**
     * @test
     **/
    public function it_show_view_with_error_if_token_is_invalid()
    {
        $email = "mail";
        $token = "_token";
        $mock_service = m::mock('StdClass')->shouldReceive('checkUserActivationCode')->once()->with($email,
            $token)->andThrow(new \Jacopo\Authentication\Exceptions\TokenMismatchException)->shouldReceive('getErrors')->once()->andReturn("")->getMock();
        App::instance('register_service', $mock_service);

        $this->action('GET', 'Jacopo\Authentication\Controllers\UserController@emailConfirmation',
            '', [
                "email" => $email,
                "token" => $token
            ]);

        $this->assertResponseOk();
        $this->assertViewHas('errors');
    }

    /**
     * @test
     **/
    public function it_show_view_errors_if_user_is_not_found()
    {
        $email = "mail";
        $token = "_token";
        $mock_service = m::mock('StdClass')->shouldReceive('checkUserActivationCode')->once()->with($email,
            $token)->andThrow(new \Jacopo\Authentication\Exceptions\UserNotFoundException())->shouldReceive('getErrors')->once()->andReturn("")->getMock();
        App::instance('register_service', $mock_service);

        $this->action('GET', 'Jacopo\Authentication\Controllers\UserController@emailConfirmation',
            '', [
                "email" => $email,
                "token" => $token
            ]);

        $this->assertResponseOk();
        $this->assertViewHas('errors');
    }

    /**
     * @test
     **/
    public function it_show_user_lists_on_lists()
    {
        \Session::put('_old_input', [
            "intersect" => "old intersect",
            "old" => "old input"
        ]);

        $this->action('GET', 'Jacopo\Authentication\Controllers\UserController@getList', [
            "new" => "new input",
            "intersect" => "new intersect"
        ]);

        $this->assertResponseOk();
    }

    /**
     * @test
     **/
    public function it_edit_user_with_success_and_redirect_to_edit_page()
    {
        $input_data = [
            "email" => $this->faker->email(),
            "password" => "password",
            "password_confirmation" => "password",
            "activated" => true
        ];

        $this->action('POST', 'Jacopo\Authentication\Controllers\UserController@postEditUser', $input_data);

        $user_created = User::firstOrFail();
        $this->assertNotNull($user_created);
        $profile_created = UserProfile::firstOrFail();
        $this->assertNotNull($profile_created);

        $this->assertRedirectedToAction('Jacopo\Authentication\Controllers\UserController@editUser',
            ['id' => $user_created->id]);
    }

    /**
     * @test
     **/
    public function canShowDashboardPage()
    {
        $mock_authenticator = m::mock('StdClass');
        $mock_authenticator->shouldReceive('getLoggedUser')->andReturn(new User());
        App::instance('authenticator', $mock_authenticator);

        $this->action('GET', 'Jacopo\Authentication\Controllers\UserController@dashboard');

        $this->assertResponseOk();
    }

    /**
     * @test
     **/
    public function canAddCustomFieldType()
    {
        $this->stopPermissionCheckEvent();
        $field_description = "field desc";
        $user_id = 1;
        $this->action('POST', 'Jacopo\Authentication\Controllers\UserController@addCustomFieldType', ['description' => $field_description, 'user_id' => $user_id]);

        $profile_fields = $this->custom_type_repository->getAllTypes();
        // check that have created a field type
        $this->assertCount(1, $profile_fields);

        $this->assertRedirectedToAction('Jacopo\Authentication\Controllers\UserController@postEditProfile', ["user_id" => $user_id]);
        $this->assertSessionHas('message');
    }

    /**
     * @test
     **/
    public function itHandleCreatePermissions()
    {
        $field_description = "field desc";
        $user_id = 1;
        $this->action('POST', 'Jacopo\Authentication\Controllers\UserController@addCustomFieldType', ['description' => $field_description, 'user_id' => $user_id]);

        $this->assertRedirectedToAction('Jacopo\Authentication\Controllers\UserController@postEditProfile', ["user_id" => $user_id]);
        $this->assertSessionHas('errors');
    }

    /**
     * @test
     **/
    public function canDeleteCustomFieldType()
    {
        $this->stopPermissionCheckEvent();
        $field_id = $this->createFieldType();
        $user_id = 1;

        $this->action('POST', 'Jacopo\Authentication\Controllers\UserController@deleteCustomFieldType', ["id" => $field_id, "user_id" => $user_id]);

        $profile_fields = $this->custom_type_repository->getAllTypes();
        $this->assertCount(0, $profile_fields);

        $this->assertRedirectedToAction('Jacopo\Authentication\Controllers\UserController@postEditProfile', ["user_id" => $user_id]);
        $this->assertSessionHas('message');
    }

    /**
     * @test
     **/
    public function itHandleDeleteErrors()
    {
        $this->stopPermissionCheckEvent();
        $user_id = 1;
        $field_id = 1;
        $this->action('POST', 'Jacopo\Authentication\Controllers\UserController@deleteCustomFieldType', ["id" => $field_id, "user_id" => $user_id]);

        $this->assertRedirectedToAction('Jacopo\Authentication\Controllers\UserController@postEditProfile', ["user_id" => $user_id]);
        $this->assertSessionHas('errors');
    }

    /**
     * @test
     **/
    public function itHandleDeletePemissionError()
    {
        $this->stopPermissionCheckCreate();
        $field_id = $this->createFieldType();
        $user_id = 1;

        $this->action('POST', 'Jacopo\Authentication\Controllers\UserController@deleteCustomFieldType', ["id" => $field_id, "user_id" => $user_id]);

        $this->assertRedirectedToAction('Jacopo\Authentication\Controllers\UserController@postEditProfile', ["user_id" => $user_id]);
        $this->assertSessionHas('errors');
    }

    /**
     * @return mixed
     */
    protected function stopPermissionCheckEvent()
    {
        $this->stopPermissionCheckDelete();
        $this->stopPermissionCheckCreate();
    }

    /**
     * @return mixed
     */
    protected function stopPermissionCheckDelete()
    {
        return Event::listen(['customprofile.deleting'], function () {
            return false;
        }, 100);
    }

    protected function stopPermissionCheckCreate()
    {
        Event::listen(['customprofile.creating',], function () {
            return false;
        }, 100);
    }

    /**
     * @return mixed
     */
    protected function createFieldType()
    {
        $description = "description";
        $field_id = $this->custom_type_repository->addNewType($description)->id;
        return $field_id;
    }
}
 