const formatNumber = n => {
  n = n.toString()
  return n[1] ? n : '0' + n
}
function getCurrentDate(){// 获取当前时间
  let date = new Date();   
  let currentYears=date.getFullYear();
  let currentMonths=date.getMonth()+1;
  let currentDay=date.getDate();
  let currentHours=date.getHours();
  let currentMinute=date.getMinutes();   
  
  var year=[currentYears].map(formatNumber)
  var mm=[currentMonths].map(formatNumber)
  var dd=[currentDay].map(formatNumber)
  var hh=[currentHours].map(formatNumber)
  var min=[currentMinute].map(formatNumber) 
  return year+'/'+mm+'/'+dd+' '+hh+':'+min;
}
 
function GetMultiIndex(){ 
	let arr=loadPickerData()
	let date = new Date();   
	let currentYears=date.getFullYear();
  let currentMonths=date.getMonth()+1;
  let currentDay=date.getDate();
  let currentHours=date.getHours();
	let currentMinute=date.getMinutes();  
	let yearindex=0
	let monthindex=0
	let dayindex=0
	let hoursindex=0
	let minuteindex=0
	arr.forEach((item,index)=>{
		switch (index) {
			case 0:
					currentYears=currentYears+'年'
					yearindex =item.indexOf(currentYears)
				break;
			case 1:
					currentMonths=[currentMonths].map(formatNumber)+'月'
					monthindex =item.indexOf(currentMonths)
				break;
			case 2:
					currentDay=[currentDay].map(formatNumber)+'日'
					dayindex =item.indexOf(currentDay)
				break;
			case 3:
					currentHours=[currentHours].map(formatNumber)+'时'
					hoursindex =item.indexOf(currentHours)
				break;
			case 4:
					currentMinute=[currentMinute].map(formatNumber)+'分'
					minuteindex =item.indexOf(currentMinute)
				break;
			default:
				break;
		}
	})
	return [yearindex,monthindex,dayindex,hoursindex,minuteindex];
} 
 
function loadYears(startYear,endYear){
  let years=[];
  for (let i = startYear; i <= endYear; i++) {
    years.push("" + i+"年");
  } 
  return years;
}
 
 
function loadMonths(startMonth,endMonth){
   let months=[];
  for (let i = startMonth; i <= endMonth; i++) {
    let val = i;
    if (val < 10) {
      val = "0" + val;
    }
    months.push("" + val+"月");
  } 
  return months;
}
 
 
function loadDays(yearSelected,selectedMonths,startDay){
    let days=[];
    if (selectedMonths == 1 || selectedMonths == 3 || selectedMonths == 5 || selectedMonths == 7 || selectedMonths == 8 || selectedMonths == 10 || selectedMonths == 12) {
      for (let i = startDay; i <= 31; i++) {
        let val = i;
        if (val < 10) {
          val = "0" + val;
        }
        days.push("" + val+'日');
      } 
    } else if (selectedMonths == 4 || selectedMonths == 6 || selectedMonths == 9 || selectedMonths == 11) {
      for (let i = startDay; i <= 30; i++) {
        let val = i;
        if (val < 10) {
          val = "0" + val;
        }
        days.push("" + val+'日');
      } 
    } else if (selectedMonths == 2) {
      let year = yearSelected 
      if (year % 4 == 0 && year % 100 != 0 || year % 400 == 0) {
        for (let i = startDay; i <= 29; i++) {
          let val = i;
          if (val < 10) {
            val = "0" + val;
          }
          days.push("" + val+'日');
        }
      } else {
        for (let i = startDay; i <= 28; i++) {
          let val = i;
          if (val < 10) {
            val = "0" + val;
          }
          days.push("" + val+'日');
        } 
      }
    }
    return days;
}
 
 
function loadHours(startHour,endHour){
   let hours=[];
   for (let i = startHour; i < endHour ; i++) {
    let val = i;
    if (val < 10) {
      val = "0" + val;
    }
    hours.push("" + val+"时");
  }
  return hours;
}
 
 
function loadMinutes(startMinute,endMinute){
 let minutes=[];
  for (let i = startMinute; i < endMinute ; i++) {
   let val = i;
   if (val < 10) {
     val = "0" + val;
   }
   minutes.push("" + val+"分");
 }
 return minutes;
}
 
function loadPickerData(){
  let date1 = new Date();   
  let currentYears=date1.getFullYear();
  let currentMonths=date1.getMonth()+1;
  let currentDay=date1.getDate();
  let currentHours=date1.getHours();
  let currentMinute=date1.getMinutes();  
  
  let years = loadYears(currentYears-2,date1.getFullYear() + 100)
  let months = loadMonths(1,12)   
  let days = loadDays(currentYears,currentMonths,1)  
  let hours = loadHours(0,24)  
  let minutes =  loadMinutes(0,60)
  return [years, months, days, hours, minutes]
}
 
export default {
  loadPickerData,
  getCurrentDate,
  GetMultiIndex,
  loadYears,
  loadMonths,
  loadDays,
  loadHours,
  loadMinutes
}
