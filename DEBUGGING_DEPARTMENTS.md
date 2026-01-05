# Debugging: Departments Blank Page Issue

**Date:** 2026-01-05
**Issue:** `/departments` URL shows blank/white page
**Status:** Under Investigation

---

## Quick Diagnostic Steps

### Step 1: Check Browser Console
1. Open browser to `http://localhost:8000/departments` (or your app URL)
2. Press **F12** to open Developer Tools
3. Click **Console** tab
4. Look for:
   - **Console Logs:** Two "Departments Page Props:" logs should appear
   - **Errors:** Any red error messages
   - **Debug Info:** Yellow debug panel should show on page

**What to look for:**
```javascript
// You should see logs like:
Departments Page Props: {
    auth: { user: {...} },
    departments: { data: [...], links: [...] },
    stats: { total_departments: 43, ... },
    ...
}

Processed Data: {
    departmentsData: [...],
    hasDepartments: true
}
```

### Step 2: Check Network Tab
1. In Developer Tools, click **Network** tab
2. Reload the page
3. Find request to `/departments`
4. Click on it
5. Check **Preview** or **Response** tab

**What to look for:**
- Status: Should be **200 OK**
- Response should have Inertia JSON structure with `component: "Departments/Index"` and `props` object

---

## Database Check

### Check if Departments Table Has Data

Run this command in your terminal:
```bash
# If using Docker:
docker-compose exec app php artisan tinker
>>> \App\Models\Department::count()

# If using PHP directly:
php artisan tinker
>>> \App\Models\Department::count()
```

**Expected result:** Should return `43` (or any number > 0)

**If result is 0:** Run the seeder:
```bash
php artisan db:seed --class=DepartmentSeeder
```

### Verify Database Connection
```bash
php artisan tinker
>>> DB::connection()->getPdo()
```

**Expected:** Should return PDO object without errors

---

## Laravel Logs Check

### Check for Server Errors
```bash
# View last 50 lines of Laravel log
tail -50 storage/logs/laravel.log

# Or watch logs in real-time
tail -f storage/logs/laravel.log
```

Then reload `/departments` page and watch for errors.

**Look for:**
- SQL errors
- Missing relationship errors
- Missing class/file errors

---

## Frontend Build Check

### Rebuild Assets
Sometimes Vite needs to rebuild the frontend:

```bash
# Stop Vite if running (Ctrl+C)
# Then restart:
npm run dev
```

### Clear Browser Cache
1. Hard refresh: **Ctrl+Shift+R** (or **Cmd+Shift+R** on Mac)
2. Or clear cache: DevTools → Application → Clear storage → Clear site data

---

## Common Issues & Solutions

### Issue 1: Database is Empty
**Symptom:** Console shows `departmentsDataLength: 0`

**Solution:**
```bash
php artisan db:seed --class=DepartmentSeeder
```

### Issue 2: JavaScript Error
**Symptom:** Red errors in browser console

**Solutions:**
- Check error message
- Rebuild assets: `npm run dev`
- Check if all imports are correct

### Issue 3: Inertia Not Rendering
**Symptom:** Network shows correct data but page is blank

**Solutions:**
- Check if `resources/js/Pages/Departments/Index.jsx` exists
- Check if component is registered in Inertia
- Rebuild assets: `npm run dev`

### Issue 4: Auth Issues
**Symptom:** Console shows `hasUser: false`

**Solutions:**
- Check if you're logged in
- Try logging out and back in
- Check session storage

### Issue 5: Route Not Found
**Symptom:** Network shows 404 error

**Solution:**
```bash
php artisan route:list | grep departments
```
Should show:
```
GET|HEAD  departments ............. departments.index
GET|HEAD  departments/create ..... departments.create
POST      departments ............. departments.store
GET|HEAD  departments/{id}/edit .. departments.edit
PUT|PATCH departments/{id} ....... departments.update
DELETE    departments/{id} ....... departments.destroy
```

---

## Debug Information on Page

I've added a **yellow debug panel** at the top of the Departments page.

**What it shows:**
- `hasAuth`: Whether auth prop exists
- `hasUser`: Whether user is authenticated
- `hasDepartments`: Whether departments prop exists
- `departmentsDataLength`: Number of departments returned
- `hasStats`: Whether stats prop exists
- `statsKeys`: What statistics are available

**This info helps identify:**
- ✅ Data is coming from backend → Frontend rendering issue
- ❌ Data is missing → Backend/database issue

---

## Next Steps Based on Findings

### If console shows data but page is blank:
1. Check AuthenticatedLayout component
2. Check if Tailwind CSS is loading
3. Try adding `<h1>Test</h1>` at top of component to see if anything renders

### If console shows no data:
1. Run database seeder
2. Check Laravel logs for errors
3. Add debug to DepartmentController:
   ```php
   public function index(Request $request)
   {
       \Log::info('Departments index called');
       $departments = Department::paginate(15);
       \Log::info('Departments count: ' . $departments->count());
       // ... rest of code
   }
   ```

### If console shows errors:
1. Fix the error based on message
2. Rebuild assets
3. Hard refresh browser

---

## Report Back

After running diagnostics, please provide:
1. **Console logs:** Screenshot or copy-paste
2. **Network response:** What data is returned?
3. **Department count:** Result from tinker
4. **Any errors:** From console or Laravel logs

With this info, I can provide exact fix.

---

## Temporary Workaround

If you need departments working immediately, try accessing via:
- Create page: `/departments/create`
- If that works, the index route has specific issue

---

**Created by:** Claude Code Assistant
**For:** TASK 2 - Fix Departments Blank Page (CRITICAL)
