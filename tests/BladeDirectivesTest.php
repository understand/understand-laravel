<?php

use Orchestra\Testbench\TestCase;
use Understand\UnderstandLaravel5\Facades\UnderstandJsProvider;

class BladeDirectivesTest extends TestCase
{
    /**
     * Setup service provider
     *
     * @param object $app
     * @return void
     */
    protected function getPackageProviders($app)
    {
        return [\Understand\UnderstandLaravel5\UnderstandLaravel5ServiceProvider::class];
    }

    /**
     * Test understandJsConfig Blade directive.
     */
    public function testUnderstandJsConfigDirective()
    {
        $result = json_decode($this->renderBlade('@understandJsConfig'), true);

        $this->assertEquals(UnderstandJsProvider::getJsConfig(), $result);
    }

    /**
     * Test understandJs Blade directive.
     */
    public function testUnderstandJsDirective()
    {
        $result = explode("\r\n", $this->renderBlade('@understandJs'));

        $this->assertEquals('<script src="' . UnderstandJsProvider::getJsBundleUrl() . '"></script>', $result[0]);
        $this->assertEquals('<script>', $result[1]);
        $this->assertStringStartsWith('Understand.init(', $result[2]);
        $this->assertStringEndsWith(');', $result[2]);

        $configuration = str_replace('Understand.init(', '', str_replace(');', '', $result[2]));

        $this->assertEquals(UnderstandJsProvider::getJsConfig(), json_decode($configuration, true));

        $this->assertEquals('Understand.installErrorHandlers();', $result[3]);
        $this->assertEquals('</script>', $result[4]);
    }

    /**
     * Render Blade output.
     *
     * @param string $viewContent
     * @return string
     */
    protected function renderBlade($viewContent)
    {
        return trim(
            $this->getCodeOutput(
                app('blade.compiler')->compileString($viewContent)
            )
        );
    }

    /**
     * Execute PHP code and return the output.
     *
     * @param string $phpCode
     * @return string
     */
    protected function getCodeOutput($phpCode)
    {
        ob_start();
        // emulate variable shared with all views
        $__env = app('view');
        eval('?>'.$phpCode);
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }
}
