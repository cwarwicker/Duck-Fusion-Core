This framework is still in development.

However, if you stumble across it somehow and want to see what it is:

- Download via git clone or zip file and install the directory into your web server
- Open your command prompt
- Navigate to /duckfusion/sys/cli
- Run the command: "/path/to/your/php duckfusion.php create-application MyApp" 
- A "MyApp" application will be created in the /duckfusion/app directory
- Open up your /app/MyApp/config/Config.php file and set a URL for the application, e.g. "http://myapp.localhost"
- Update your web server to point the address "myapp.localhost" to the /app/MyApp/web directory, e.g. in Apache's httpd.conf file
- If using Windows, update your C:/Windows/system32/drivers/etc/hosts file and add the line: "127.0.0.1 myapp.localhost" and save, that will let you access that host
- Now open your web browser and navigate to "http://myapp.localhost" and voila.