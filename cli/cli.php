<?php
namespace DF;

define('df_CLI', true);

require_once '../core/Darkmatter.php';

class CLI
{
    
    public $actions = array(
        'new-app'
    );
    
    private $app;
    private $dir;
    private $ns;
    
    public function isActionValid($action){
        return (in_array($action, $this->actions));
    }
    
    public function callAction($action, $param){
                
        switch($action)
        {
            case 'new-app':
                $this->runNewApp($param);
            break;
        }
                
    }
    
    private function runNewApp($param){
        
        // Strip any non-alphanumeric characters from name
        $app = preg_replace("/[^a-z 0-9 \-_]/i", "", $param);
        $this->app = $app;
                
        $namespace = preg_replace("/[^a-z_]/i", "", $this->app);
        $this->ns = $namespace;
            
        
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
            echo "Error: Cannot create directory. Permission denied\n";
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
        $this->writeRouterFile();
        $this->writeSettingFile();
        $this->writeIndexControllerFile();
        $this->writeIndexTemplateFile();
        $this->writeIndexViewFile();
        $this->writeLangFile();
        $this->writeCssFile();
        $this->writeHtaccessFile();
        $this->writeIndexFile();
        
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
    
    
    private function writeRouterFile()
    {
        
        if ($this->app)
        {
            
            
            $content = file_get_contents(df_SYS . 'cli' . df_DS . 'dist' . df_DS . 'router.php');
            $content = str_replace("%ns%", $this->ns, $content);

            $file = fopen($this->dir . 'config' . df_DS . 'Router.php', 'w');
            if (!$file)
            {
                echo "Error: Cannot create Router file in " . $this->dir . 'config' . df_DS . "\n";
                exit;
            }
            
            // Write content
            fwrite($file, $content);
            fclose($file);
            
            echo "Router file created...\n";
            
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
$param = (isset($_SERVER['argv'][2])) ? $_SERVER['argv'][2]: false;

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