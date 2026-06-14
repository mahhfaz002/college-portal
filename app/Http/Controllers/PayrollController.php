<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Salary;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PayrollController extends Controller
{
    public function index()
    {
        $employees = Employee::all();
        $salaries = Salary::with('employee')->latest()->take(20)->get();

        return view('hr.index', compact('employees', 'salaries'));
    }

    // Generate Monthly Salary
    public function generatePayroll($month)
    {
        $employees = Employee::all();

        foreach ($employees as $employee) {
            // Attendance-based deductions removed with the attendance system.
            $absentDays = 0;
            $deduction = $absentDays * ($employee->base_salary / 30);
            $netSalary = $employee->base_salary - $deduction;

            Salary::create([
                'employee_id' => $employee->id,
                'month' => $month,
                'gross_salary' => $employee->base_salary,
                'deductions' => $deduction,
                'net_salary' => $netSalary,
                'payment_status' => 'Pending',
            ]);
        }

        return back()->with('success', 'Payroll generated for ' . $month);
    }
}
