export async function fetchSettings() {
  try {
    const response = await fetch("/wp-json/my-easy-compta/v1/settings/get", {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": myEasyComptaAdmin.nonce,
      },
    });

    if (response.ok) {
      const settings = await response.json();
      let currencySymbol = null;
      let vatData = null;
      let listVatData = null;

      if (settings.default_currency) {
        const currencyId = settings.default_currency;
        const currencyResponse = await fetch(
          `/wp-json/my-easy-compta/v1/settings/currency/${currencyId}`,
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (currencyResponse.ok) {
          const currencyData = await currencyResponse.json();
          currencySymbol = currencyData.symbol;
        } else {
          console.error(
            "Failed to fetch currency data:",
            currencyResponse.statusText
          );
        }
      }

      if (settings.vat_active == 1) {
        const vatId = settings.default_vat;
        const vatResponse = await fetch(
          `/wp-json/my-easy-compta/v1/settings/vat/${vatId}`,
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (vatResponse.ok) {
          vatData = await vatResponse.json();
        } else {
          console.error("Failed to fetch VAT data:", vatResponse.statusText);
        }

        const listVatsResponse = await fetch(
          `/wp-json/my-easy-compta/v1/settings/vats`,
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (listVatsResponse.ok) {
          listVatData = await listVatsResponse.json();
        } else {
          console.error(
            "Failed to fetch VAT data:",
            listVatsResponse.statusText
          );
        }
      }

      return {
        settings,
        currencySymbol,
        vatData,
        listVatData,
      };
    } else {
      const error = await response.json();
      throw new Error(error.message);
    }
  } catch (error) {
    console.error(error);
    throw new Error(error.message);
  }
}
