export function calculateVAT(amount, vatRate, currencySymbol = "€") {
  const totalAmount = parseFloat(amount);
  const defaultVAT = parseFloat(vatRate);
  const vatAmount = totalAmount * (defaultVAT / 100);
  const totalWithVAT = totalAmount + vatAmount;
  const formattedTotalWithVAT = totalWithVAT
    .toFixed(2)
    .replace(/\B(?=(\d{3})+(?!\d))/g, " ");

  return `${formattedTotalWithVAT} ${currencySymbol}`;
}

export function calculateWithoutVAT(amount, currencySymbol = "€") {
  const totalAmount = parseFloat(amount);
  const formattedTotal = totalAmount
    .toFixed(2)
    .replace(/\B(?=(\d{3})+(?!\d))/g, " ");

  return `${formattedTotal} ${currencySymbol}`;
}

export function generatePaginationButtons(currentPage, totalPages) {
  let paginationButtons = [];

  if (totalPages <= 5) {
    for (let i = 1; i <= totalPages; i++) {
      paginationButtons.push(i);
    }
  } else {
    if (currentPage <= 3) {
      for (let i = 1; i <= 5; i++) {
        paginationButtons.push(i);
      }
      paginationButtons.push("...");
      paginationButtons.push(totalPages);
    } else if (currentPage > totalPages - 3) {
      paginationButtons.push(1);
      paginationButtons.push("...");
      for (let i = totalPages - 4; i <= totalPages; i++) {
        paginationButtons.push(i);
      }
    } else {
      paginationButtons.push(1);
      paginationButtons.push("...");
      for (let i = currentPage - 1; i <= currentPage + 1; i++) {
        paginationButtons.push(i);
      }
      paginationButtons.push("...");
      paginationButtons.push(totalPages);
    }
  }

  return paginationButtons;
}

export function formatAmount(amount, currency, currencyPosition) {
  const formattedAmount = parseFloat(amount)
    .toFixed(2)
    .replace(/\B(?=(\d{3})+(?!\d))/g, " ");

  if (currencyPosition === "before") {
    return `${currency} ${formattedAmount}`;
  } else {
    return `${formattedAmount} ${currency}`;
  }
}

export function parseDate(dateStr, format) {
  if (!dateStr || !format) return null;

  const formatParts = format.split(/[-./]/);
  const dateParts = dateStr.split(/[-./]/);

  if (formatParts.length !== dateParts.length) return null;

  const dayIndex = formatParts.indexOf("DD");
  const monthIndex = formatParts.indexOf("MM");
  const yearIndex = formatParts.indexOf("YYYY");

  const day = dayIndex !== -1 ? parseInt(dateParts[dayIndex], 10) : null;
  const month =
    monthIndex !== -1 ? parseInt(dateParts[monthIndex], 10) - 1 : null;
  const year = yearIndex !== -1 ? parseInt(dateParts[yearIndex], 10) : null;

  const parsedDate = new Date(year, month, day);
  return parsedDate.toString() !== "Invalid Date" ? parsedDate : null;
}

export function showToast(toast, message, type) {
  toast.message = message;
  toast.type = type;
  toast.visible = true;
  setTimeout(() => {
    toast.visible = false;
  }, 3000);
}
