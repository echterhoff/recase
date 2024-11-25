<?php
/**
 * reCase closes the case. Rule'em all!
 * Put a string in any proper given convention in and pass a key or a description of the case you want it to transform in.
 * Straight forward, unreadable, regex-packed code.
 * Don't try to comprehend it, just use it. Me as author won't comprehend in some days either.
 * Supports: flatcase
 *
 * @param string $string A string that has a distinguishable case convention.
 * @param string $toCase A sting that looks like you expect the input to look after transformation.
 * @param array $options Allows for two options passed as array: `['debug' => null, 'throw' => null]`
 * @param array $log Array that collects insights about the decisions within the process.
 * @return string
 * 
 * @author Lars.Echterhoff (at gmail)
 */
function recase(
    string $string,
    string $toCase = 'camelCase',
    array $options = ['debug' => null, 'throw' => null],
    array &$log = []
): string {
    //Parameter definitions
    global $ifDebug;
    global $ifThrow;
    $ifDebug = !empty($options['debug']);
    $ifThrow = !empty($options['throw']);

    //Closure definitions
    $caseLib = fn($case) => match ($case) {
        $name = 'flatcase' => [$name => ['/([a-zäüöß][a-zäüöß0-9]+)/']],
        $name = 'upperflatcase' => [$name => ['/([A-ZÄÜÖ][A-ZÄÜÖ0-9]+)/']],
        $name = 'camelcase' => [$name => ['/([A-ZÄÜÖ][^A-ZÄÜÖ]+)/', '/([a-zäüöß]+)/']],
        $name = 'pascalcase' => [$name => ['/([A-ZÄÜÖ][^A-ZÄÜÖ]*)/']],
        $name = 'screamingsnakecase' => [$name => ['/_([A-ZÄÜÖ]+)/', '/([^_]+)/']],
        $name = 'snakecase' => [$name => ['/_([^_]+)/', '/([^_]+)/']],
        $name = 'camelsnakecase' => [$name => ['/_([A-ZÄÜÖ][^A-ZÄÜÖ_]*)/', '/([A-ZÄÜÖ][^_]*)/']],
        $name = 'traincase' => [$name => ['/-([^-]+)/', '/([^\-]+)/']],
        $name = 'screamingkebabcase' => [$name => ['/-([^-]+)/', '/([^\-]+)/']],
        $name = 'kebabcase' => [$name => ['/-([^-]+)/', '/([^\-]+)/']],
        $name = 'titlecase' => [$name => ['/ ([^ ]+)/', '/([^\ ]+)/']],
        $name = 'humancase' => [$name => ['/ ([^ ]*)/', '/([^ ][^ ]*)/']],
        $name = 'underscorenotation' => [$name => ['/_([^_]+)/']],
        $name = 'dotcase' => [$name => ['/\.([^.]+)/', '/([^.]+)/']],
        $name = 'pathcase' => [$name => ['#/([^/]+)#', '#([^/]+)#']],
        $name = 'unidentified' => [$name => ['/(.*)/']],
        default => null
    };

    $caseNormalize = fn($case, &$param = null) => is_array(
        $result = $caseLib($case) ?: $caseLib(preg_replace('/^[a-zäüöß]/u', '', strtolower($case)))
    ) ? key($result) . @empty($param = array_values(current($result))) : $result;

    $identifiedInputStyle = fn(string $string, ?array &$param = null) => match (true) {
        (bool)preg_match('#^[a-zäüöß][^A-ZÄÜÖ +._/\\\-]+$#u', $string) => $caseNormalize('flatcase', $param),
        (bool)preg_match('#^[A-ZÄÜÖ][^a-zäüöß +._/\\\-]+$#u', $string) => $caseNormalize('upperflatcase', $param),
        (bool)preg_match('#^[a-zäüö]+(?:[A-ZÄÜÖ][^A-ZÄÜÖ]+)+$#u', $string) => $caseNormalize('camelcase', $param),
        (bool)preg_match(
            '#^[A-ZÄÜÖ]+?(?:[^A-ZÄÜÖ +._/\\\-]+[A-ZÄÜÖ]+|[^A-ZÄÜÖ +._/\\\-])+$#u',
            $string
        ) => $caseNormalize('pascalcase', $param),
        (bool)preg_match('#^[A-ZÄÜÖ0-9]+?(?:_+?[^a-zäüöß +._/\\\-]+)+$#u', $string) => $caseNormalize(
            'screamingsnakecase',
            $param
        ),
        (bool)preg_match('#^[^A-ZÄÜÖ +._/\\\-]+(?:_[^A-ZÄÜÖ +._/\\\-]+)+$#u', $string) => $caseNormalize(
            'snakecase',
            $param
        ),
        (bool)preg_match(
            '#^[A-ZÄÜÖ][^A-ZÄÜÖ +_./\\\-]*?(?:_+[A-ZÄÜÖ][^A-ZÄÜÖ +._/\\\-]*)+$#u',
            $string
        ) => $caseNormalize('camelsnakecase', $param),
        (bool)preg_match(
            '#^[A-Z0-9ÄÜÖ][^A-ZÄÜÖ +_./\\\-]*?(?:-+[A-ZÄÜÖ][^A-ZÄÜÖ +_./\\\-]*?)+$#u',
            $string
        ) => $caseNormalize('traincase', $param),
        (bool)preg_match('#^[A-Z0-9ÄÜÖ]+(?:-+(?:[A-ZÄÜÖ]|[^ +_./\\\s-])+)+$#u', $string) => $caseNormalize(
            'screamingkebabcase',
            $param
        ),
        (bool)preg_match('#^[^A-ZÄÜÖ0-9 +._/\\\-]+(?:-+[^A-ZÄÜÖ +._/\\\-]+)+$#u', $string) => $caseNormalize(
            'kebabcase',
            $param
        ),
        (bool)preg_match(
            '#^[A-Z0-9ÄÜÖ][^A-ZÄÜÖ +_./\\\-]*?(?: +[A-ZÄÜÖ][^A-ZÄÜÖ +_./\\\-]*?)+$#u',
            $string
        ) => $caseNormalize('titlecase', $param),
        (bool)preg_match('#^\w+(?: +\w+)+$#u', $string) => $caseNormalize('humancase', $param),
        (bool)preg_match('#^_+(?:[A-ZÄÜÖa-zäüöß0-9]+)+$#u', $string) => $caseNormalize('underscorenotation', $param),
        (bool)preg_match('#^[\wäüößÄÜÖ+_-]+(?:\.+[\wäüößÄÖÜ+_-]+)+$#u', $string) => $caseNormalize('dotcase', $param),
        (bool)preg_match('#^[\wäüößÄÜÖ+_-]+(?:/+[\wäüößÄÜÖ+_-]+)+$#u', $string) => $caseNormalize('pathcase', $param),
        default => $caseNormalize('unidentified', $param)
    };

    $ucf = fn(string $string) => mb_strtoupper(mb_substr($string, 0, 1)) . mb_substr($string, 1);
    $lcf = fn(string $string) => mb_strtolower(mb_substr($string, 0, 1)) . mb_substr($string, 1);
    $map = fn(callable $callable, array $array) => array_map($callable, $array);
    $stl = fn(string $string) => mb_strtolower($string);
    $stu = fn(string $string) => mb_strtoupper($string);
    $glu = fn(string $char, array $array) => implode($char, $array);

    $recaseToken = fn(array $token, $exportCase) => match ($exportCase) {
        'flatcase' => $glu('', $map($stl, $token)),
        'upperflatcase' => $glu('', $map($stu, $token)),
        'camelcase' => $lcf($glu('', $map($ucf, $map($stl, $token)))),
        'pascalcase' => $glu('', $map($ucf, $map($stl, $token))),
        'titlecase' => $glu(' ', $map($ucf, $map($stl, $token))),
        'screamingsnakecase' => $glu('_', $map($stu, $token)),
        'snakecase' => $glu('_', $map($stl, $token)),
        'camelsnakecase' => $glu('_', $map($ucf, $map($stl, $token))),
        'traincase' => $glu('-', $map($ucf, $map($stl, $token))),
        'screamingkebabcase' => $glu('-', $map($stu, $token)),
        'kebabcase' => $glu('-', $map($stl, $token)),
        'humancase' => $glu(' ', $token),
        'underscorenotation' => '_' . $lcf($glu('', $map($ucf, $map($stl, $token)))),
        'dotcase' => $glu('.', $token),
        'pathcase' => $glu('/', $token),
        'unidentified' => $glu('', $token),
    };

    $tokenize = function (string $string, string $rule, string $startRule = '') use ($ifThrow, &$log): array {
        $iteration = 0;
        $token = [];
        do {
            if (!preg_match(
                $appliedRule = ($iteration == 0 && $startRule ? $startRule : $rule) . 'u',
                $string,
                $match
            )) {
                $msg = sprintf(
                    'Could not match a valid token piece #%d with "%s" on "%s". Probably an invalid rule.',
                    $iteration,
                    $appliedRule,
                    $string
                );
                $ifThrow ? throw new LogicException($msg) : user_error($msg, E_USER_ERROR);
            }
            $token[] = $match[1];
            $string = mb_substr($string, mb_strlen($match[0]));
            if ((++$iteration) > 10000) {
                $msg = 'Too many token within string or logical error while cutting token into pieces.';
                $ifThrow ? throw new LogicException($msg) : user_error($msg, E_USER_ERROR);
            }
        } while ($string);
        $log[] = sprintf('INFO: It took %d iterations to split the string.', $iteration);
        return $token;
    };

    $inputStyle = $identifiedInputStyle($string, $param);
    $targetCase = $toCase;
    !$ifDebug ?: print_r(($log[] = sprintf("INFO: Identified InputStyle: %s", $inputStyle)) . "\n");

    $output = $string;
    if ($param) {
        $log[] = sprintf('INFO: Found instructions to split string into tokens.');
        array_unshift($param, $string);
        $token = $tokenize(...$param);
        $targetCase = $caseNormalize($toCase);
        if (!$targetCase) {
            $log[] = sprintf('INFO: Given toCase "%s" is not a known target case style name.', $toCase);
            $targetCase = $identifiedInputStyle($toCase);
            $log[] = sprintf('INFO: Given toCase "%s" has been identified as style "%s".', $toCase, $targetCase);
        }
        if ($targetCase === 'unidentified') {
            $log[] = "WARNING: toCase parameter resolved as unidentified output style.";
            $targetCase = $inputStyle;
        }

        if ($inputStyle === 'unidentified') {
            $targetCase = $inputStyle;
        }
        $output = $recaseToken($token, $targetCase);
    }

    !$ifDebug ?: print_r(
        ($log[] = sprintf(
            'Input: %s Style: %s / Output: %s Style: %s',
            $string,
            $inputStyle,
            $output,
            $targetCase
        )) . "\n"
    );

    return $output;
}