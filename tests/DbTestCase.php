<?php  namespace Jacopo\Authentication\Tests;

/**
 * Class DbTestCase
 *
 * @author jacopo beschi jacopo@jacopobeschi.com
 */
use Artisan;
use BadMethodCallException;
use DB;

class DbTestCase extends TestCase
{
    protected $artisan;
    protected $times = 1;
    protected $faker;

    public function setUp()
    {
        parent::setUp();

        $this->artisan = $this->app->make('artisan');
        $this->createDbSchema();

        $this->faker = \Faker\Factory::create();
    }

    protected function make($class_name, $fields = [])
    {
        $created_objs = [];
        while ($this->times--) {
            $stub_data = array_merge($this->getModelStub(), $fields);
            $created_objs[] = $class_name::create($stub_data);
        }

        return $created_objs;
    }

    protected function getModelStub()
    {
        throw new BadMethodCallException("You need to implement this method in your own class.");
    }

    protected function times($count)
    {
        $this->times = $count;

        return $this;
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // reset base path to point to our package's src directory
        $app['path.base'] = __DIR__ . '/../src';

        $test_connection = array(
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        );

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', $test_connection);

    }

    protected function createDbSchema()
    {
        $this->artisan->call('migrate', ["--database" => "testbench", '--path' => '../src/migrations']);
    }

    protected function objectHasAllArrayAttributes(array $attributes, $object, array $except = [])
    {
        foreach ($attributes as $key => $value) {
            if (!in_array($key, $except)) $this->assertEquals($value, $object->$key);
        }

    }

    protected function assertObjectHasAllAttributes(array $attributes, $object, array $except = [])
    {
        $this->objectHasAllArrayAttributes($attributes, $object, $except);
    }
} 