import dotenv from 'dotenv'; dotenv.config();
import { getEmployeeByQrOrId } from '../services/attendanceService.js';

(async ()=>{
  for (const qr of ['QR_EMP001','QR_EMP002','QR_EMP003','QR_EMP004']){
    try {
      const emp = await getEmployeeByQrOrId({ qrCode: qr });
      console.log(qr, '=>', emp ? `${emp.id} ${emp.name}` : 'NOT FOUND');
    } catch (e) {
      console.error(qr, 'lookup error:', e?.message || e);
    }
  }
})();
