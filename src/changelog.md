# 2024/july

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
