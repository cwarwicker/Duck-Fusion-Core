<?php
namespace DF;

define('df_CLI', true);

require_once __DIR__ . '/../core/Darkmatter.php';

class CLI
{
    
    /**
     * Array of possible commands. Array keys are all the possible aliases that a command can have, with the actual command as the value of the element
     * @var type 
     */
    public $commands = array(
        
        'create-application' => 'create-application',
        'create-app' => 'create-application',
        
        'delete-application' => 'delete-application',
        'delete-app' => 'delete-application',
        
        'add-module' => 'add-module',
        'add-mod' => 'add-module',
        
        'delete-module' => 'delete-module',
        'delete-mod' => 'delete-module',
        
        'commands' => 'list-commands',
        'list-commands' => 'list-commands'
        
    );
    
    private $app; // Name of application
    private $dir; // Directory of object (application/module/etc...)
    private $ns; // Namespace of application to use
    private $mod; // Name of module
    
    /**
     * Check if a given command alias is valid
     * @param type $action
     * @return type
     */
    public function isCommandValid($action){
        return (array_key_exists($action, $this->commands));
    }
    
    /**
     * Get the actual name of a command from its alias
     * @param type $alias
     * @return type
     */
    private function getAliasCommand($alias){
        return (isset($this->commands[$alias])) ? $this->commands[$alias] : null;
    }
    
    /**
     * Get all the commands with all their possible aliases as well
     * @return type
     */
    private function getAllCommands(){
        
        $return = array();
        
        foreach($this->commands as $alias => $command)
        {
            
            // Set the command into the array if it's not already there
            if (!array_key_exists($command, $return)){
                $return[$command] = array();
            }
            
            // Append the alias
            $return[$command][] = $alias;
            
        }
        
        return $return;
        
    }
    
    /**
     * Call the command from the user input
     * @param type $command
     * @param type $param
     */
    public function callCommand($command, $param){
                
        // Get the actual command from the alias
        $command = $this->getAliasCommand($command);
        
        switch($command)
        {
        
            // List all the available commands
            case 'list-commands':
                $this->runListCommands();
            break;
            
            // Create a new appplication
            case 'create-application':
                $this->runCreateApplication($param);
            break;
        
            // Add a new module to an application
            case 'add-module':
                $this->runAddModule($param);
            break;
        
            // Delete an existing application
            case 'delete-application':
                $this->runDeleteApplication($param);
            break;
        
            // Delete an existing module from an application
            case 'delete-module':
                $this->runDeleteModule($param);
            break;
        
        
        }
                
    }
    
    /**
     * Get the namespace from the fully qualified name
     * @param type $name
     * @return type
     */
    private function getNameSpaceFromAppName($name){
        $namespace = preg_replace("/[^a-z_]/i", "", $name);
        return $namespace;
    }
    
    /**
     * Get the namespace from the application name
     * @return type
     */
    private function getAppNameSpace(){
        return $this->getNameSpaceFromAppName($this->app);
    }
    
    /**
     * Run the list-commands command
     */
    private function runListCommands(){
        
        $commands = $this->getAllCommands();
        
        echo "Valid commands:\n\n";
        
        foreach($commands as $command => $aliases)
        {
            echo $command . " (aliases: ".implode("|", $aliases).")\n";
        }
        
    }
   
    
    /**
     * Run the create-application command to create a new application
     * @param type $param
     */
    private function runCreateApplication($param){
        
        if (!$param){
            echo "Missing parameter\n\n";
            \DF\CLI::displayHelp();
        }
        
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
            echo "Error: Cannot create directory ({$this->dir}). Permission denied\n";
            exit;
        }
        
        // Copy the dist app
        $this->copyDirectory(df_SYS . 'cli' . df_DS . 'dist' . df_DS . 'app', $this->dir);
        
        // Write files
        $this->writeSettingFile();
        $this->writeIndexControllerFile();
        $this->writeIndexTemplateFile();
        $this->writeLangFile();
        $this->writeConfigFile();
        
        echo "Application ({$this->app}) successfully created!\n\n";
        
    }
    
    /**
     * Run the delete-application command to delete an application directory
     * @param type $param
     */
    private function runDeleteApplication($param){
        
        if (!$param){
            echo "Missing parameter\n\n";
            \DF\CLI::displayHelp();
        }
        
        $this->app = (isset($param[0])) ? $param[0] : null;
        $confirmed = (isset($param[1]) && strtolower($param[1]) == '-y');
        
        // Make sure directory exists
        $dir = df_ROOT . 'app' . df_DS . $this->app . df_DS;
        if (!is_dir($dir)){
            echo "Error: Directory {$dir} does not exist\n\n";
            exit;
        }
        
        // Is it confirmed?
        if (!$confirmed){
            echo "Confirmation: Running this command will completely destroy the {$dir} directory...\n";
            echo "To confirm that you are happy to do this, please append the '-y' flag to the end, e.g. 'delete-application {$this->app} -y'\n\n";
            exit;
        }
        
        // Delete the directory
        if (!$this->deleteDirectory($dir)){
            echo "Error: Unable to delete directory {$dir}. Sorry!\n\n";
            exit;
        }
        
        echo "Application {$this->app} successfully deleted!\n\n";
        
    }
    
    
     /**
     * Run the add-module command, to add a new module to an existing application
     * @param type $param
     */
    private function runAddModule($param){
                
        if (!$param){
            echo "Missing parameter\n\n";
            \DF\CLI::displayHelp();
        }
        
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
        
        // Copy the dist app
        $this->copyDirectory(df_SYS . 'cli' . df_DS . 'dist' . df_DS . 'module', $this->dir);       
        
        // Write files
        $this->writeModuleControllerFile();
        $this->writeModuleTemplateFile();        
        
    }
    
    /**
     * Run the delete-module command to delete a module from an application
     * @param type $param
     */
    private function runDeleteModule($param){
        
        if (!$param){
            echo "Missing parameter\n\n";
            \DF\CLI::displayHelp();
        }
        
        $this->app = (isset($param[0])) ? $param[0] : null;
        $this->mod = (isset($param[1])) ? $param[1] : false;
        $confirmed = (isset($param[2]) && strtolower($param[2]) == '-y');
        
        if (!$this->app || !$this->mod){
            echo "Error: Invalid parameter\n\n";
            self::displayHelp();
            exit;
        }
        
        // Make sure directory exists
        $dir = df_ROOT . 'app' . df_DS . $this->app . df_DS . 'modules' . df_DS . $this->mod;
        if (!is_dir($dir)){
            echo "Error: Directory {$dir} does not exist\n\n";
            exit;
        }
        
        // Is it confirmed?
        if (!$confirmed){
            echo "Confirmation: Running this command will completely destroy the {$dir} directory...\n";
            echo "To confirm that you are happy to do this, please append the '-y' flag to the end, e.g. 'delete-application {$this->app} -y'\n\n";
            exit;
        }
        
        // Delete the directory
        if (!$this->deleteDirectory($dir)){
            echo "Error: Unable to delete directory {$dir}. Sorry!\n\n";
            exit;
        }
        
        echo "Module {$this->mod} successfully deleted from application {$this->app}!\n\n";
        
    }
    
    /**
     * Recursively delete a directory and all its contents
     * @param type $dir
     * @return type
     */
    private function deleteDirectory($dir){
        
        $files = array_diff( scandir($dir), array('.', '..') );
        foreach($files as $file){
            (is_dir($dir . df_DS . $file)) ? $this->deleteDirectory($dir . df_DS . $file) : unlink($dir . df_DS . $file);
        }
        return rmdir($dir);
        
    }
    
    
    /**
    * Copy a file, or recursively copy a folder and its contents
    * @author      Aidan Lister <aidan@php.net>
    * @version     1.0.1
    * @link        http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
    * @param       string   $source    Source path
    * @param       string   $dest      Destination path
    * @param       int      $permissions New folder creation permissions
    * @return      bool     Returns true on success, false on failure
    */
   function copyDirectory($source, $dest, $permissions = 0755)
   {
       
       // Simple copy for a file
       if (is_file($source)) {
           $result = copy($source, $dest);
           if ($result){
                echo "Created file ({$dest})...\n";
           }
           return $result;
       }

       // Make destination directory
       if (!is_dir($dest)) {
           if ( mkdir($dest, $permissions) ){
               echo "Created directory ({$dest})...\n";
           }
       }

       // Loop through the folder
       $dir = dir($source);
       while (false !== $entry = $dir->read()) {
           // Skip pointers
           if ($entry == '.' || $entry == '..') {
               continue;
           }

           // Deep copy directories
           $this->copyDirectory("$source/$entry", "$dest/$entry", $permissions);
       }

       // Clean up
       $dir->close();
       return true;
       
   }
   
    
    /**
     * Write changes to the default Settings.php file
     */
    private function writeSettingFile()
    {
        
        if ($this->app)
        {
            
            $file = $this->dir . 'classes' . df_DS . 'Setting.php';
            $content = file_get_contents($file);
            $content = str_replace("%ns%", $this->ns, $content);
            
            $file = file_put_contents($file, $content);
            if (!$file)
            {
                echo "Error: Cannot write Setting class file in " . $this->dir . 'classes' . df_DS . "\n";
                exit;
            }
           
            
            echo "Setting class file written...\n";
            
        }
        
    }
        
    /**
     * Write changes to the default IndexTemplate.php file
     */
    private function writeIndexTemplateFile()
    {
        
        if ($this->app)
        {
            
            $file = $this->dir . 'views' . df_DS . 'IndexTemplate.php';
            $content = file_get_contents($file);
            $content = str_replace("%ns%", $this->ns, $content);
            
            $file = file_put_contents($file, $content);
            if (!$file)
            {
                echo "Error: Cannot write IndexTemplate file in " . $this->dir . 'views' . df_DS . "\n";
                exit;
            }

            echo "IndexTemplate file written...\n";
            
        }
        
    }
    
    /**
     * Write changes to default lang.php file
     */
    private function writeLangFile()
    {
        
        if ($this->app)
        {
            
            $file = $this->dir . 'lang' . df_DS . 'en' . df_DS . 'lang.php';
            $content = file_get_contents($file);
            $content = str_replace("%title%", $this->app, $content);
            
            $file = file_put_contents($file, $content);
            if (!$file)
            {
                echo "Error: Cannot write lang file in " . $this->dir . 'lang/en' . df_DS . "\n";
                exit;
            }
            
            echo "lang file written...\n";
            
        }
        
    }
    
    /**
     * Write changes to the IndexController.php file
     */
    private function writeIndexControllerFile()
    {
        
        if ($this->app)
        {
            
            $file = $this->dir . 'controllers' . df_DS . 'IndexController.php';
            $content = file_get_contents($file);
            $content = str_replace("%ns%", $this->ns, $content);

            $file = file_put_contents($file, $content);
            if (!$file)
            {
                echo "Error: Cannot write IndexController file in " . $this->dir . 'controllers' . df_DS . "\n";
                exit;
            }
            
            echo "IndexController file written...\n";
            
        }
        
    }
    
    
    /**
     * Write changes to the default Config file
     */
    private function writeConfigFile()
    {
        
        if ($this->app)
        {
            
            $file = $this->dir . 'config' . df_DS . 'Config.php';
            $content = file_get_contents($file);
            $content = str_replace("%title%", $this->app, $content);

            $file = file_put_contents($file, $content);
            if (!$file)
            {
                echo "Error: Cannot write Configuration file in " . $this->dir . 'config' . df_DS . "\n";
                exit;
            }
                        
            echo "Configuration file written...\n";
            
        }
        
        
    }
    
     
    /**
     * Create the Controller script for the module
     */
    private function writeModuleControllerFile()
    {
        
        if ($this->app && $this->mod)
        {
            
            $modName = ucfirst($this->mod);
            $file = $this->dir . df_DS . 'controllers' . df_DS . 'ModuleController.php';
            $content = file_get_contents($file);
            $content = str_replace("%mod%", $modName, $content);
            $content = str_replace("%ns%", $this->ns, $content);

            $result = file_put_contents($file, $content);
            if (!$result)
            {
                echo "Error: Cannot write ModuleController file in " . $this->dir . 'controllers' . df_DS . "\n";
                exit;
            }
            
            // Rename file
            if (!rename($file, $this->dir . df_DS . 'controllers' . df_DS . $modName . 'Controller.php')){
                echo "Error: Cannot rename ModuleController file\n";
                exit;
            }
            
            echo "{$modName}Controller file written...\n";
            
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
            $file = $this->dir . df_DS . 'views' . df_DS . 'ModuleTemplate.php';
            $content = file_get_contents($file);
            $content = str_replace("%mod%", $modName, $content);
            $content = str_replace("%ns%", $this->ns, $content);

            $result = file_put_contents($file, $content);
            if (!$result)
            {
                echo "Error: Cannot write ModuleTemplate file in " . $this->dir . 'views' . df_DS . "\n";
                exit;
            }
            
            // Rename file
            if (!rename($file, $this->dir . df_DS . 'views' . df_DS . $modName . 'Template.php')){
                echo "Error: Cannot rename ModuleTemplate file\n";
                exit;
            }
            
            echo "{$modName}Template file written...\n";
            
        }
        
    }
    
    
    
    /**
     * Display the help information
     * @param type $action
     */
    public static function displayHelp($action = false)
    {
        
        $output = "";
        
        $output .= "Proper usage: php duckfusion.php action parameter\n\n";
        $output .= "e.g. php duckfusion.php create-app MyTestApp\n\n";
        $output .= "type: 'php duckfusion.php commands' for a list of commands";
        
        echo $output;
        exit;
        
    }
    
}

// Get the user input
$action = (isset($_SERVER['argv'][1])) ? $_SERVER['argv'][1]: false;
$param = (count($_SERVER['argv'] > 2)) ? array_slice($_SERVER['argv'], 2): false;

// Check they have actually supplied an action
if (!$action){
    echo "Missing action\n\n";
    \DF\CLI::displayHelp();
}

// Build the CLI object
$CLI = new \DF\CLI();

// Make sure the command is valid
if (!$CLI->isCommandValid($action)){
    echo "Invalid action\n\n";
    echo "Type 'commands' for a list of all available commands\n\n";
    exit;
}

// Run it
$CLI->callCommand($action, $param);
exit;