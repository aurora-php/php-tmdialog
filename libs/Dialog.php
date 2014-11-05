<?php

/*
 * This file is part of the 'octris/php-tmdialog' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\TMDialog;

/**
 * Class for handling TextMate dialog server (tmdialog) dialogs.
 *
 * @copyright   copyright (c) 2014 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Dialog
{
    /**
     * TMDialog token.
     *
     * @type    string
     */
    protected $token;

    /**
     * Instance of plist parser.
     *
     * @type    \Octris\TMDialog\Plist
     */
    protected $plist;

    /**
     * Action registry.
     *
     * @type    array
     */
    protected $actions = array();

    /**
     * Predefined actions.
     *
     * @type    int
     */
    const ACTION_CLOSE = -1;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->plist = new \Octris\TMDialog\Plist();

        $this->registerAction('closeWindow', function ($model) {
            return self::ACTION_CLOSE;
        });
    }

    /**
     * Register an action.
     *
     * @param   string          $action         Name of action to register.
     * @param   callable        $callback       Callback for action.
     */
    public function registerAction($action, callable $callback)
    {
        $this->actions[$action] = $callback;
    }

    /**
     * Convert a PHP array to a TMDialog model.
     *
     * @param   array           $params         Optional parameters for model.
     * @return  string                          TMDialog model.
     */
    public function toModel(array $params = array())
    {
        $model = '';

        foreach ($params as $k => $v) {
            $model .= sprintf('%s = "%s"; ', $k, $v);
        }

        return '{ ' . $model . '}';
    }

    /**
     * Load a NIB dialog file.
     *
     * @param   string          $dialog         Name of dialog.
     * @param   array           $params         Optional parameters.
     * @return  bool                            Returns true on success.
     */
    public function load($dialog, array $params = array())
    {
        $nib = sprintf(
            '%s/php/nibs/%s.nib/',
            $_ENV['TM_BUNDLE_SUPPORT'],
            basename($dialog, '.nib')
        );

        if (!is_dir($nib)) {
            // NIB not found
            return false;
        }

        $cmd = sprintf(
            '%s nib --load %s --model %s',
            escapeshellarg($_ENV['DIALOG']),
            escapeshellarg($nib),
            escapeshellarg($this->toModel($params))
        );

        $this->token = `$cmd`;

        return ((int)$this->token > 0);
    }

    /**
     * Update dialog with new data.
     *
     * @param   array           $params         Optional parameters.
     */
    public function update(array $params = array())
    {
        $cmd = sprintf(
            '%s nib --update %d --model %s',
            escapeshellarg($_ENV['DIALOG']),
            $this->token,
            escapeshellarg($this->toModel($params))
        );

        `$cmd`;
    }

    /**
     * Close dialog.
     */
    public function dispose()
    {
        $cmd = sprintf(
            '%s nib --dispose %d',
            escapeshellarg($_ENV['DIALOG']),
            $this->token
        );

        `$cmd`;
    }

    /**
     * Run dialog.
     */
    public function run()
    {
        $cmd = sprintf(
            '%s nib --wait %d',
            escapeshellarg($_ENV['DIALOG']),
            $this->token
        );

        do {
            $plist = `$cmd`;
            $data  = $this->plist->process($plist);

            if (!is_array($data) || count($data) == 0) {
                break;
            }

            $action = (isset($data['eventInfo']) ? $data['eventInfo']['type'] : '');
            $model  = (isset($data['model']) ? $data['model'] : null);

            if (isset($this->actions[$action])) {
                $status = (int)$this->actions[$action]($model);
            } else {
                $status = 0;
            }
        } while ($status >= 0);
    }
}
