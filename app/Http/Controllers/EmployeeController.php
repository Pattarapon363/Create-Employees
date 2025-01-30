<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\table;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    //การแสดงรายการพนักงาน
    public function index(Request $request)
    {
        $query = $request->input('search');
        $sortColumn = $request->input('sort', 'emp_no');//ระบุคอลัมน์ที่ต้องการจัดเรียง
        $sortDirection = $request->input('direction', 'asc');//ระบุทิศทางการจัดเรียง

        $employees = DB::table('employees')
            ->where('first_name', 'like', '%' . $query . '%')//ค้นหาชื่อ
            ->orWhere('last_name', 'like', '%' . $query . '%')//หานามสกุุล
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(20);//จัดเรียงข้อมูลด้วยorderByและแบ่งหน้าแสดงผลข้อมูล20รายการ

        return Inertia::render('Employee/Index', [
            'employees' => $employees,//รายการพนักงาน
            'query' => $query,//คำค้นหา
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,//คอลัมน์และทิศทางการจัดเรียง
        ]);// ส่งข้อมูลไปที่หน้าEmployee/Indexพร้อมข้อมูล
    }

    /**
     * การแสดงฟอร์มสร้างพนักงานใหม่
     */
    public function create()
    {
        // ดึงรายชื่อแผนกจากฐานข้อมูล เพือ่ ไปแสดงให้เลือกรายการในแบบฟอร,ม
        $departments = DB::table('departments')->select('dept_no', 'dept_name')->get();

        // ส่งข้อมูลไปยังหน้า Inertia(อินเนอเทีย)
        return inertia('Employee/Create', ['departments' => $departments]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validate the incoming data
            $validated = $request->validate([
                'first_name' => 'required|string|max:14', //ชื่อต้องไม่เกิน 14 ตัวอักษร
                'last_name' => 'required|string|max:16',//นามสกุลต้องไม่เกิน 16 ตัวอักษร
                'gender' => 'required|in:M,F',//เพศต้องเป็นM(ชาย)หรือF(หญิง)
                'hire_date' => 'required|date',//วันที่เริ่มงานต้องเป็นรูปแบบวันที่
                'birth_date' => 'required|date', //วันเกิดต้องเป็นรูปแบบวันที่
                'dept_no' => 'required|exists:departments,dept_no',//รหัสแผนกต้องมีอยู่ในฐาน
                'photo_path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'//รูปภาพต้องเป็นไฟล์ที่รองรับและไม่เกิน2MB
            ]);

            DB::transaction(function () use ($validated, $request) {

                //สร้างหมายเลขพนักงาน (emp_no) ใหม่
                $latestEmpNo = DB::table('employees')->max('emp_no') ?? 0; //ถ้ายังไม่มีพนักงาน(max(emp_no)เป็น nullใช้ค่าเริ่มต้นเป็น 0
                $newEmpNo = $latestEmpNo + 1;

                if ($request->hasFile('photo_path')) {
                    $photoPath = $request->file('photo_path')->store('photos', 'public'); //เก็บใน storage/app/public/photos
                }

                //เพิ่มข้อมูลพนักงานในตาราง employee
                DB::table('employees')->insert([
                    'emp_no' => $newEmpNo,
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'gender' => $validated['gender'],
                    'hire_date' => $validated['hire_date'],
                    'birth_date' => $validated['hire_date'],
                    'photo_path' => $photoPath,
                ]);

                //เชื่อมโยงพนักงานกับแผนก
                DB::table('dept_emp')->insert([
                    'emp_no' => $newEmpNo,
                    'dept_no' => $validated['dept_no'],
                    'from_date' => now(),
                    'to_date' => '9999-01-01', //กำหนดto_dateเป็น'9999-01-01'เพื่อบอกว่าเป็นแผนกปัจจุบัน
                ]);
            });
            // บันทึกข้อมูลการร้องขอลง Log
            Log::info($request->all());
            // Redirect ไปหน้ารายชื่อพนักงาน
            return redirect()->route('employees.index')//เปลี่ยนเส้นทางไปที่หน้ารายชื่อพนักงาน
                ->with('success', 'Employee created successfully.');//ส่งFlashMessageว่าเพิ่มพนักงานสำเร็จ

        //จัดการข้อผิดพลาด
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return back()->with('error', 'Failed to create employee. Please try again.');
        }//รีเทินกลับไปหน้าฟอร์มพร้อมข้อความ "เพิ่มพนักงานไม่สำเร็จ"
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        //

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        //
    }
}
