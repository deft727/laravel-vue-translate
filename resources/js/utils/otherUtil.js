const DATES = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']
const DATES_FULL = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']
const MONTHS = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"]
const getSubTitle = (sub_title) => {
  var arr = sub_title.split(" ")
  var title_head = arr[0]
  return title_head;
}

const getDateInfo = (d, active, disabled) => {
  var date = d.getDay(); // 0-6
  var day = d.getDate(); // 0-31
  // var month= d.getMonth(); // 0-11
  // var year=date.getFullYear(); // yyyy
  return {date: d, lbl_day: day, lbl_date: DATES[date], active, disabled }
}

const getNewDate = (obj_date, type, selected_date) => {
  var date, active = false, disabled = false
  if(type === 'prev'){
    date = new Date(obj_date.date.getTime() - (24 * 60 * 60 * 1000));  
  }else{
    date = new Date(obj_date.date.getTime() + (24 * 60 * 60 * 1000));  
  }
  if(date.getTime() == selected_date?.date.getTime()) {
    active = selected_date.active
    disabled = selected_date.disabled
  }
  return getDateInfo(date, active, disabled)
}
const nth = function(d) {
  if (d > 3 && d < 21) return 'th';
  switch (d % 10) {
    case 1:  return "st";
    case 2:  return "nd";
    case 3:  return "rd";
    default: return "th";
  }
}
const getStrDate = (d) => {
  var date = d.getDay(); // 0-6
  var day = d.getDate(); // 1-31
  var month= d.getMonth(); // 0-11
  return DATES_FULL[date] + ', ' + MONTHS[month] + ' ' + day + nth(day)
}

const wait = (ms) => {
    return new Promise(resolve => setTimeout(() => resolve(''), ms));
}
export { getSubTitle, getDateInfo, getNewDate, getStrDate, wait }