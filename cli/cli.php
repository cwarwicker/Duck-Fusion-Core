<?php
namespace DF;

define('df_CLI', true);

require_once '../core/Darkmatter.php';

class CLI
{
    
    public $actions = array(
        'new-app',
        'add-module'
    );
    
    private $app;
    private $dir;
    private $ns;
    private $mod;
    
    public function isActionValid($action){
        return (in_array($action, $this->actions));
    }
    
    public function callAction($action, $param){
                
        switch($action)
        {
            case 'new-app':
                $this->runNewApp($param);
            break;
            case 'add-module':
                $this->runAddModule($param);
            break;
        }
                
    }
    
    private function getNameSpaceFromAppName($name){
        $namespace = preg_replace("/[^a-z_]/i", "", $name);
        return $namespace;
    }
    
    private function getAppNameSpace(){
        return $this->getNameSpaceFromAppName($this->app);
    }
    
    private function runAddModule($param){
        
        $app = (isset($param[0])) ? $param[0] : false;
        $module = (isset($param[1])) ? $param[1] : false;
        
        // Strip any non-alphanumeric characters from name
        $this->app = preg_replace("/[^a-z 0-9 \-_]/i", "", $app);
        $this->mod = preg_replace("/[^a-z 0-9 \-_]/i", "", $module);
        $this->ns = $this->getAppNameSpace();
        
        // Make sure module directory doesn't already exist
        $dir = df_ROOT . 'app' . df_DS . $this->app . df_DS . 'modules' . df_DS . $this->mod . df_DS;
        if (is_dir($dir)){
            echo "Error: Directory {$dir} already exists\n";
            echo "Either choose a new name for your module, or remove the existing directory\n\n";
            exit;
        }
        
        $this->dir = $dir;
        
        // Create app directory
        if ( mkdir($this->dir, 0755) ){
            echo "Created module directory ({$dir})...\n";
        } else {
            echo "Error: Cannot create directory. Permission denied\n";
            exit;
        }
        
        // Create all sub directories
        $folders = array(
            'controllers',
            'models',
            'views'
        );
        
        foreach($folders as $folder){
            
            if ( mkdir($this->dir . df_DS . $folder, 0755) ){
                echo "Created {$folder} directory in {$this->dir}...\n";
            } else {
                echo "Error: Cannot create directory ({$folder}). Permission denied.\n";
                exit;
            }
            
        }
        
        // Write files
        $this->writeModuleControllerFile();
        $this->writeModuleTemplateFile();
        $this->writeModuleMainViewFile();
        
        
    }
    
    /**
     * Create a new app
     * @param type $param
     */
    private function runNewApp($param){
        
        if (is_array($param)){
            $param = reset($param);
        }
        
        // Strip any non-alphanumeric characters from name
        $app = preg_replace("/[^a-z 0-9 \-_]/i", "", $param);
        $this->app = $app;
                
        $this->ns = $this->getAppNameSpace();
            
        
        // Make sure directory doesn't already exist
        $dir = df_ROOT . 'app' . df_DS . $this->app . df_DS;
        
        if (is_dir($dir)){
            echo "Error: Directory {$dir} already exists\n\n";
            echo "Either choose a new name for your app, or remove the existing directory\n\n";
            exit;
        }
        
        $this->dir = $dir;
        
        // Create app directory
        if ( mkdir($this->dir, 0755) ){
            echo "Created app directory ({$this->app})...\n";
        } else {
            echo "Error: Cannot create directory ({$folder}). Permission denied\n";
            exit;
        }
        
        // Create all sub directories
        $folders = array(
            'classes',
            'config',
            'controllers',
            'data',
            'lang',
            'lang/en',
            'modules',
            'tmp',
            'tmp/cache',
            'tmp/logs',
            'views',
            'views/theme',
            'views/theme/default',
            'web',
            'web/css',
            'web/css/theme',
            'web/css/theme/default',
            'web/img',
            'web/js'
        );
        
        foreach($folders as $folder){
            
            if ( mkdir($this->dir . df_DS . $folder, 0755) ){
                echo "Created {$folder} directory in {$this->app}...\n";
            } else {
                echo "Error: Cannot create directory. Permission denied.\n";
                exit;
            }
            
        }
        
        // Write files
        $this->writeConfigFile();
        $this->writeRoutesFile();
        $this->writeSettingFile();
        $this->writeIndexControllerFile();
        $this->writeIndexTemplateFile();
        $this->writeIndexViewFile();
        $this->writeLangFile();
        $this->writeCssFile();
        $this->writeHtaccessFile();
        $this->writeIndexFile();
        
    }
    
    /**
     * Create the Controller script for the module
     */
    private function writeModuleControllerFile()
    {
        
        if ($this->app && $this->mod)
        {
            
            $modName = ucfirst($this->mod);
            $content = file_get_contents(df_SYS . 'cli' . df_DS . 'dist' . df_DS . 'modulecontroller.php');
            $content = str_replace("%mod%", $modName, $content);
            $content = str_replace("%ns%", $this->ns, $content);

            $file = fopen($this->dir . 'controllers' . df_DS . $modName . 'Controller.php', 'w');
            if (!$file)
            {
                echo "Error: Cannot create {$modName}Controller file in " . $this->dir . 'controllers' . df_DS . "\n";
                exit;
            }
            
            // Write content
            fwrite($file, $content);
            fclose($file);
            
            echo "{$modName}Controller file created...\n";
            
        }
        
    }
    
    /**
     * Create the Template script for the module
     */
    private function writeModuleTemplateFile()
    {
        
        if ($this->app && $this->mod)
        {
            
            $modName = ucfirst($this->mod);
            $content = file_get_contents(df_SYS . 'cli' . df_DS . 'dist' . df_DS . 'moduletemplate.php');
            $content = str_replace("%mod%", $modName, $content);
            $content = str_replace("%ns%", $this->ns, $content);

            $file = fopen($this->dir . 'views' . df_DS . $modName . 'Template.php', 'w');
            if (!$file)
            {
                echo "Error: Cannot create {$modName}Template file in " . $this->dir . 'views' . df_DS . "\n";
                exit;
            }
            
            // Write content
            fwrite($file, $content);
            fclose($file);
            
            echo "{$modName}Template file created...\n";
            
        }
        
    }
    
    /**
     * Create the main template file for the module
     */
    private function writeModuleMainViewFile()
    {
        
        if ($this->app && $this->mod)
        {
            
            $modName = ucfirst($this->mod);
            $content = file_get_contents(df_SYS . 'cli' . df_DS . 'dist' . df_DS . 'modulemain.html');
            $content = str_replace("%mod%", $modName, $content);

            $file = fopen($this->dir . 'views' . df_DS . 'main.html', 'w');
            if (!$file)
            {
                echo "Error: Cannot create main.html file in " . $this->dir . 'views' . df_DS . "\n";
                exit;
            }
            
            // Write content
            fwrite($file, $content);
            fclose($file);
            
            echo "main.html file created...\n";
            
        }
        
    }
    
    private function writeSettingFile()
    {
        
        if ($this->app)
        {
            
            $content = file_get_contents(df_SYS . 'cli' . df_DS . 'dist' . df_DS . 'setting.php');
            $content = str_replace("%ns%", $this->ns, $content);
            
            $file = fopen($this->dir . 'classes' . df_DS . 'Setting.php', 'w');
            if (!$file)
            {
                echo "Error: Cannot create Setting class file in " . $this->dir . 'classes' . df_DS . "\n";
                exit;
            }
            
            // Write content
            fwrite($file, $content);
            fclose($file);
            
            echo "Setting class file created...\n";
            
        }
        
    }
    
    private function writeIndexFile()
    {
        
        if ($this->app)
        {
            
            $content = file_get_contents(df_SYS . 'cli' . df_DS . 'dist' . df_DS . 'index.php');
            
            $file = fopen($this->dir . 'web' . df_DS . 'index.php', 'w');
            if (!$file)
            {
                echo "Error: Cannot create index.php file in " . $this->dir . 'web' . df_DS . "\n";
                exit;
            }
            
            // Write content
            fwrite($file, $content);
            fclose($file);
            
            echo "index.php file created...\n";
            
        }
        
    }
    
    private function writeHtaccessFile()
    {
        
        if ($this->app)
        {
            
            $content = file_get_contents(df_SYS . 'cli' . df_DS . 'dist' . df_DS . 'htaccess');
            
            $file = fopen($this->dir . 'web' . df_DS . '.htaccess', 'w');
            if (!$file)
            {
                echo "Error: Cannot create .htaccess file in " . $this->dir . 'web' . df_DS . "\n";
                exit;
            }
            
            // Write content
            fwrite($file, $content);
            fclose($file);
            
            echo ".htaccess file created (this may require tweaking for your environment)...\n";
            
        }
        
    }
    
    private function writeCssFile()
    {
        
        if ($this->app)
        {
            
            
            $content = file_get_contents(df_SYS . 'cli' . df_DS . 'dist' . df_DS . 'app.css');
            
            $file = fopen($this->dir . 'web' . df_DS . 'css' . df_DS . 'theme' . df_DS . 'default' . df_DS . 'app.css', 'w');
            if (!$file)
            {
                echo "Error: Cannot create app.css file in " . $this->dir . 'css/theme/default' . df_DS . "\n";
                exit;
            }
            
            // Write content
            fwrite($file, $content);
            fclose($file);
            
            echo "app.css file created...\n";
            
        }
        
    }
    
    private function writeIndexViewFile()
    {
        
        if ($this->app)
        {
            
            
            $content = file_get_contents(df_SYS . 'cli' . df_DS . 'dist' . df_DS . 'index.html');
            
            $file = fopen($this->dir . 'views' . df_DS . 'index.html', 'w');
            if (!$file)
            {
                echo "Error: Cannot create index.html file in " . $this->dir . 'views' . df_DS . "\n";
                exit;
            }
            
            // Write content
            fwrite($file, $content);
            fclose($file);
            
            echo "index.html file created...\n";
            
        }
        
    }
    
    private function writeIndexTemplateFile()
    {
        
        if ($this->app)
        {
            
            
            $content = file_get_contents(df_SYS . 'cli' . df_DS . 'dist' . df_DS . 'indextemplate.php');
            $content = str_replace("%ns%", $this->ns, $content);
            
            $file = fopen($this->dir . 'views' . df_DS . 'IndexTemplate.php', 'w');
            if (!$file)
            {
                echo "Error: Cannot create IndexTemplate file in " . $this->dir . 'views' . df_DS . "\n";
                exit;
            }
            
            // Write content
            fwrite($file, $content);
            fclose($file);
            
            echo "IndexTemplate file created...\n";
            
        }
        
    }
    
    private function writeLangFile()
    {
        
        if ($this->app)
        {
            
            
            $content = file_get_contents(df_SYS . 'cli' . df_DS . 'dist' . df_DS . 'lang.php');
            $content = str_replace("%title%", $this->app, $content);
            
            $file = fopen($this->dir . 'lang' . df_DS . 'en' . df_DS . 'lang.php', 'w');
            if (!$file)
            {
                echo "Error: Cannot create lang file in " . $this->dir . 'lang/en' . df_DS . "\n";
                exit;
            }
            
            // Write content
            fwrite($file, $content);
            fclose($file);
            
            echo "lang file created...\n";
            
        }
        
    }
    
    private function writeIndexControllerFile()
    {
        
        if ($this->app)
        {
            
            
            $content = file_get_contents(df_SYS . 'cli' . df_DS . 'dist' . df_DS . 'indexcontroller.php');
            $content = str_replace("%ns%", $this->ns, $content);

            $file = fopen($this->dir . 'controllers' . df_DS . 'IndexController.php', 'w');
            if (!$file)
            {
                echo "Error: Cannot create IndexController file in " . $this->dir . 'controllers' . df_DS . "\n";
                exit;
            }
            
            // Write content
            fwrite($file, $content);
            fclose($file);
            
            echo "IndexController file created...\n";
            
        }
        
    }
    
    
    private function writeRoutesFile()
    {
        
        if ($this->app)
        {
            
            $content = file_get_contents(df_SYS . 'cli' . df_DS . 'dist' . df_DS . 'routes.php');

            $file = fopen($this->dir . 'config' . df_DS . 'Routes.php', 'w');
            if (!$file)
            {
                echo "Error: Cannot create Routes file in " . $this->dir . 'config' . df_DS . "\n";
                exit;
            }
            
            // Write content
            fwrite($file, $content);
            fclose($file);
            
            echo "Routes file created...\n";
            
        }
        
    }
    
    private function writeConfigFile()
    {
        
        if ($this->app)
        {
            
            
            $content = file_get_contents(df_SYS . 'cli' . df_DS . 'dist' . df_DS . 'config.php');
            $content = str_replace("%title%", $this->app, $content);
            $content = str_replace("%ns%", $this->ns, $content);

            $file = fopen($this->dir . 'config' . df_DS . 'Config.php', 'w');
            if (!$file)
            {
                echo "Error: Cannot create Configuration file in " . $this->dir . 'config' . df_DS . "\n";
                exit;
            }
            
            // Write content
            fwrite($file, $content);
            fclose($file);
            
            echo "Configuration file created (This will require tweaking with your server settings)...\n";
            
        }
        
        
    }
    
    public static function displayHelp($action = false)
    {
        
        $output = "";
        
        $output .= "Proper usage: php cli.php action parameter\n\n";
        $output .= "e.g. php cli.php new-app MyTestApp\n\n";
        
        echo $output;
        exit;
        
    }
    
}

$action = (isset($_SERVER['argv'][1])) ? $_SERVER['argv'][1]: false;
$param = (count($_SERVER['argv'] > 2)) ? array_slice($_SERVER['argv'], 2): false;

if (!$action){
    echo "Missing action\n\n";
    \DF\CLI::displayHelp();
}

if (!$param){
    echo "Missing parameter\n\n";
    \DF\CLI::displayHelp();
}

$CLI = new \DF\CLI();

if (!$CLI->isActionValid($action)){
    echo "Invalid action\n\n";
    echo "Acceptable actions:\n\n";
    echo implode("\n", $CLI->actions);
    exit;
}

$CLI->callAction($action, $param);