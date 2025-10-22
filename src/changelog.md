### 2.1.20
2025-10
	- **improvement:** improving MagratheaApi function and documentation
	- **improvement:** improving debugging for unknown errors

### 2.1.19
2025-10
	- **improvement:** improving functions for Magrathea Admin
	- **improvement:** improving CORS
	- **improvement:** improving MagratheaApi function and documentation

### 2.1.18
2025-09-30
	- **new:** config now getting environment variables when starting with `$=`
	- **improvement:** PHP 8.4: dealing with some deprecations...

### 2.1.17
2025-06-18
	- **fix:** Magrathea API debugger fixing closure functions

### 2.1.16
2025-05-19
	- **fix:** Create new Admin User with md5 password
	- **fix:** Update Admin User with md5 password

### 2.1.13
2025-02-11
	- **fix:** ConfigApp saving

### 2.1.11
2025-01-01
	- **improvement:** admin improvements
	- **improvement:** improving insert query generator

### 2.1.9
2024-12-11
	- **improvement:** `GetAppRoot` function on `MagratheaPHP`

### 2.1.8
2024-12-03
	- **fix:** deleting filed cache fixed

### 2.1.7
2024-12-02
post PNC Update
	- **fix:** deleting cache of settings when updating settings
	- **fix:** changing `MagratheaCache->DeleteFile` with new parameter `$addExtension`, default to `true`
	- **fix:** Calling Admin `Initialize` on load
	- **change:** changelog moved to inside `src` for easier deploy
	- **improvement:** log cache delete 
	- **new:** `LogLastError` function on `Logger`
	- **new:** reloading cache button
	- **new:** function `MinVersion` on `MagratheaPHP`
	- **new:** `CacheClearPattern` function on `MagratheaApiControl`

### 2.1.5
by Paulo Martins
dromedario.etc update
	- fix Admin Logo
	- getting put data on ApiControl
	- fixing update from basic api crud
	- fix Join Object 
	- improved `ToArray` model function
	- `CacheClear` function on `MagratheaApiControl`
	- automatic cache on CRUD requests
	- cache fix
	- delete log file from admin

### 2.1.3
by Paulo Martins
	- delete objects from magrathea_objects.conf
	- API fallback
	- move authentication endpoints to parent ApiAuthentication class
