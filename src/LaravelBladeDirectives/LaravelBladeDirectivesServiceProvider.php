<?php

namespace LaravelBladeDirectives;

use Illuminate\Support\ServiceProvider;

class LaravelBladeDirectivesServiceProvider extends ServiceProvider {

    public function boot() {
        $this->registerDotJsBladeDirectives();
        $this->registerUsefulBladeDirectives();
    }

    /**
     * Configs for PHP Storm IDE:
     *
     * Settings -> Languages & Frameworks -> PHP -> Blade -> Directives
     *
     * For directives: partial
     * Has parameter: true
     * Prefix: <?php $str =
     * Suffix: ; ?>
     *
     * For directives: yieldPartial, yieldpartial
     * Has parameter: true
     * Prefix: <?php $fn = function(string $partialName, array $partialData = []) {}; $fn(
     * Suffix: ); ?>
     *
     * For directives: string, trans, html, unescaped
     * Has parameter: true
     * Prefix: <?php echo trans(
     * Suffix: ); ?>
     *
     * For directives: key
     * Has parameter: true
     * Prefix: <?php $fn = function(array $array, $key, $default = null, bool $escaped = true) {} ; $fn(
     * Suffix: ); ?>
     *
     * For directives without parameters: breakpoint, endPartial, endpartial
     * Has parameter: false
     */
    protected function registerUsefulBladeDirectives() {
        // @breakpoint
        \Blade::directive('breakpoint', function () {
            return '<?php if(function_exists("xdebug_break")){ xdebug_break(); } ?>';
        });
        // @partial('section_name')
        \Blade::directive('partial', function ($expression) {
            return <<<PARTIAL
                <?php \$__partials[$expression] = function (array \$envData, array \$customData) { 
                    extract(\$envData);
                    extract(\$customData);
                ?>
PARTIAL;
        });
        $endPartial = function () {
            return '<?php } ?>';
        };
        // @endPartial
        \Blade::directive('endPartial', $endPartial);
        // @endpartial
        \Blade::directive('endpartial', $endPartial);

        $yieldPartial = function ($expression) {
            return <<<PARTIAL
                <?php
                    \$arguments = [$expression];
                    array_get(\$__partials, \$arguments[0], function () {})(\$__env->getShared(), (array)array_get(\$arguments, 1, []));
                ?> 
PARTIAL;
        };
        // @yieldPartial('section_name', ['var1' => 'value1', ...])
        \Blade::directive('yieldPartial', $yieldPartial);
        // @yieldpartial('section_name', ['var1' => 'value1', ...])
        \Blade::directive('yieldpartial', $yieldPartial);

        // @string('path.to.translation')
        // @trans('path.to.translation')
        $closure = function ($expression) {
            return "<?php echo e(trans($expression)); ?>";
        };
        \Blade::directive('string', $closure);
        \Blade::directive('trans', $closure);


        // @html('path.to.translation')
        // @unescaped('path.to.translation')
        $closure = function ($expression) {
            return "<?php echo trans($expression); ?>";
        };
        \Blade::directive('html', $closure);
        \Blade::directive('unescaped', $closure);

        // @key($array, 'array key', $default = null, bool $escaped = true)
        \Blade::directive('key', function ($expression) {
            $parts = explode(',', $expression);
            if (isset($parts[3]) && trim($parts[3]) === 'false') {
                // unescaped
                return "<?php echo array_get($expression); ?>";
            } else {
                return "<?php echo e(array_get($expression)); ?>";
            }
        });

    }

    /**
     * Configs for PHP Storm IDE:
     *
     * Settings -> Languages & Frameworks -> PHP -> Blade -> Directives
     *
     * For directives: jsEcho, jsEchoEncoded, jsEval, jsForEach, jsIf, jsElseIf, jsIfSimple, jsElseIfSimple
     * Has parameter: true
     * Prefix: <?php echo `
     * Suffix: `; ?>
     *
     * For directives without parameters: jsElse, jsEndIf, jsEndForEach
     * Has parameter: false
     *
     * Additional:
     * Configure how `text in tilda quotes` displayed via
     * Settings -> Editor -> Color Scheme -> PHP -> Shell command
     */
    private function registerDotJsBladeDirectives() {
        // {{= it.value }}
        $closure = function ($expression) {
            $expression = $this->processDotJsExpressionWithPhpCodeInside($expression);
            return "<?php echo '@{{= {$expression} }}' ?>";
        };
        \Blade::directive('jsEcho', $closure);
        \Blade::directive('jsecho', $closure);

        // {{! it.value }}
        $closure = function ($expression) {
            $expression = $this->processDotJsExpressionWithPhpCodeInside($expression);
            return "<?php echo '@{{! {$expression} }}' ?>";
        };
        \Blade::directive('jsEchoEncoded', $closure);
        \Blade::directive('jsechoencoded', $closure);

        // {{ it._some_name = value }}
        // {{ it._some_name = '` . $phpVar . `' }}
        // {{ it._some_name = "` . SOME_CONSTANT . `" }}
        // {{ it._some_name = "` . ($phpVar + 1) . `" }}
        // Note that next $expression might work incorrectly!:
        // it._some_name = "` . $type['value'] . `asdasd` . `"
        $closure = function ($expression) {
            $expression = $this->processDotJsExpressionWithPhpCodeInside($expression);
            return "<?php echo '@{{ {$expression}; }}' ?>";
        };
        \Blade::directive('jsEval', $closure);
        \Blade::directive('jseval', $closure);

        // {{? it.value }}
        // {{? it.value === '` . $phpVar . `' }}
        // {{? it.value === "` . SOME_CONSTANT . `" }}
        // {{? it.value === "` . ($phpVar + 1) . `" }}
        // Note that next $expression might work incorrectly!:
        // it.value === "` . $type['value'] . `asdasd` . `"
        $closure = function ($expression) {
            $expression = $this->processDotJsExpressionWithPhpCodeInside($expression);
            return "<?php echo '@{{? {$expression} }}' ?>";
        };
        \Blade::directive('jsIf', $closure);
        \Blade::directive('jsif', $closure);

        // {{? it.value }}
        // PHP expressions ignored
        $closure = function ($expression) {
            $expression = str_replace("'", "\\'", $expression);
            return "<?php echo '@{{? {$expression} }}' ?>";
        };
        \Blade::directive('jsIfSimple', $closure);
        \Blade::directive('jsifsimple', $closure);

        // {{?? it.value }}
        // {{?? it.value === '` . $phpVar . `' }}
        // {{?? it.value === "` . SOME_CONSTANT . `" }}
        // {{?? it.value === "` . ($phpVar + 1) . `" }}
        // Note that next $expression might work incorrectly!:
        // it.value === "` . $type['value'] . `asdasd` . `"
        $closure = function ($expression) {
            $expression = $this->processDotJsExpressionWithPhpCodeInside($expression);
            return "<?php echo '@{{?? {$expression} }}' ?>";
        };
        \Blade::directive('jsElseIf', $closure);
        \Blade::directive('jselseif', $closure);

        // {{?? it.value }}
        // PHP expressions ignored
        $closure = function ($expression) {
            $expression = str_replace("'", "\\'", $expression);
            return "<?php echo '@{{?? {$expression} }}' ?>";
        };
        \Blade::directive('jsElseIfSimple', $closure);
        \Blade::directive('jselseifsimple', $closure);

        // {{??}}
        $closure = function () {
            return "<?php echo '@{{??}}' ?>";
        };
        \Blade::directive('jsElse', $closure);
        \Blade::directive('jselse', $closure);

        // {{?}}
        $closure = function () {
            return "<?php echo '@{{?}}' ?>";
        };
        \Blade::directive('jsEndIf', $closure);
        \Blade::directive('jsendif', $closure);

        // {{~ it.array :value:key }} => @jsForEach(it.array as key => value) or @jsForEach(it.array as value)
        $closure = function ($expression) {
            if (preg_match('%^(.+) as (.+?)(?:\s*=>\s*(.+))?$%', $expression, $matches)) {
                // php-styled format of foreach
                if (empty($matches[3])) {
                    // $expression = 'it.array as value' => {{~ it.array :value }}
                    $expression = "{$matches[1]} :{$matches[2]}";
                } else {
                    // $expression = 'it.array as key => value' => {{~ it.array :value:key }}
                    $expression = "{$matches[1]} :{$matches[3]}:{$matches[2]}";
                }
            }
            $expression = str_replace("'", "\\'", $expression);
            return "<?php echo '@{{~ {$expression} }}' ?>";
        };
        \Blade::directive('jsForEach', $closure);
        \Blade::directive('jsforeach', $closure);

        // {{~}}
        $closure = function () {
            return "<?php echo '@{{~}}' ?>";
        };
        \Blade::directive('jsEndForEach', $closure);
        \Blade::directive('jsendforeach', $closure);
    }

    protected function processDotJsExpressionWithPhpCodeInside($dotJsExpression): string {
        $expression = str_replace("'", "\\'", $dotJsExpression);
        // replace {{ $phpExpression }} by "' . ($phpExpression) . '"
        return preg_replace_callback("%`\s*\.\s*(.+?)\s*\.\s*`%", function ($matches) {
            $insert = str_replace("\\'", "'", $matches[1]);
            return "'. ($insert) . '";
        }, $expression);
    }

}