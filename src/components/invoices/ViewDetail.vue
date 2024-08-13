<template>
  <div class="pt-2 pr-4">
    <InvoiceNavBar
      :invoiceInfo="invoice"
      :currencyDefault="defaultCurrency"
      :currencyClient="clientCurrency"
      :emailActive="settings.easy_compta_email_addon_active"
    />
    <remove-modal
      :show-modal="showRemoveModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      :confirmText="translations.yes_delete_it"
      :cancelText="translations.cancel"
      @confirm="this.removeItem(selectedItem, SelectedInvoiceId)"
      @cancel="showRemoveModal = false"
    />
    <div
      v-if="toast.visible"
      :class="['toast', toast.position]"
      :style="{ zIndex: 9999 }"
    >
      <div :class="['alert', toast.type, 'text-white']">
        <span>{{ toast.message }}</span>
      </div>
    </div>
    <div
      v-if="loading"
      class="fixed top-0 left-0 w-full h-full flex items-center justify-center bg-gray-900 bg-opacity-50 z-50"
    >
      <span class="loading loading-spinner text-primary loading-lg"></span>
    </div>

    <div v-if="isInvoiceOverdue && invoice.status == 'unpaid'">
      <div role="alert" class="alert alert-warning">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-6 w-6 shrink-0 stroke-current"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
        <span>{{ translations.invoice_overdue }}</span>
      </div>
    </div>
    <Card topMargin="mt-8" id="invoice-content">
      <div class="grid grid-cols-2">
        <div class="md:col-span-1">
          <div>
            <img
              :src="settings.logo_url"
              :style="{ width: settings.logo_width + 'px' }"
              alt="Logo"
            />
          </div>
        </div>
        <div class="md:col-span-1 text-right">
          <p class="text-lg font-semibold">{{ invoice.invoice_number }}</p>
          <div>
            {{ translations.created_at }}:
            <strong>{{ invoice.created_at }}</strong>
          </div>
          <div>
            {{ translations.due_date }}:
            <strong>{{ invoice.due_date }}</strong>
          </div>
          <div>
            {{ translations.status }}:
            <span
              v-if="invoice.status == 'draft'"
              class="badge badge-error text-white"
              >{{ translations.draft }}</span
            >
            <span
              v-if="invoice.status == 'unpaid'"
              class="badge badge-error text-white"
              >{{ translations.unpaid }}</span
            >
            <span
              v-if="invoice.status == 'paid'"
              class="badge badge-success text-white"
              >{{ translations.paid }}</span
            >
            <span
              v-if="invoice.status == 'cancelled'"
              class="badge badge-secondary text-white"
              >{{ translations.cancelled }}</span
            >
          </div>
        </div>
      </div>

      <div
        class="bg-base-300 rounded-lg shadow-md flex justify-between p-4 mt-4 gap-4"
      >
        <div>
          <strong>{{ translations.bill_to }}:</strong>
          <h4>
            <strong>{{ client_detail.company_name }}</strong>
          </h4>
          <p>
            {{ client_detail.address }}<br />
            {{ client_detail.postal_code }}, {{ client_detail.city }} <br />
            {{ client_detail.country }}<br />
            <a
              v-if="client_detail.phone"
              href="tel:{{ client_detail.phone }}"
              >{{ client_detail.phone }}</a
            >
          </p>
        </div>
        <div>
          <strong>{{ translations.received_from }}:</strong>
          <h4>
            <strong>{{ settings.company_name }}</strong>
          </h4>
          <p>
            {{ settings.company_address }}<br />
            {{ settings.postal_code }}, {{ settings.city }} <br />
            {{ settings.country }}<br />
            <a
              v-if="settings.company_phone"
              href="tel:{{ settings.company_phone }}"
              >{{ settings.company_phone }}</a
            ><br />
            <a
              v-if="settings.mobile_phone"
              href="tel:{{ settings.mobile_phone }}"
              >{{ settings.mobile_phone }}</a
            >
          </p>
        </div>
      </div>
      <edit-item-modal
        :loading="loadingModal"
        :show-modal="editItemsModal"
        modal-id="modal_edit_item"
        :modal-title="translations.edit_item"
        :item="selectedItem"
        @close="editItemsModal = false"
        @itemEdited="fetchItems"
      />

      <form @submit.prevent="submitItems">
        <table class="table mt-8">
          <thead>
            <tr>
              <th></th>
              <th width="5%">{{ translations.item_ref }}</th>
              <th width="19%">{{ translations.item_name }}</th>
              <th width="21%">{{ translations.description }}</th>
              <th width="10%" class="text-center">
                {{ translations.quantity }}
              </th>
              <th width="8%" class="text-center">
                {{ translations.unit_price }}
              </th>
              <th width="5%" class="text-center">
                {{ translations.vat }}
              </th>
              <th width="10%" class="text-center">
                {{ translations.discount }}
              </th>
              <th width="10%" class="text-right">{{ translations.total }}</th>
              <th width="18%" class="text-right inv-actions"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in invoiceItems" :key="item.id || index">
              <td class="draggable-item drag-handle px-2">
                <i class="fas fa-bars"></i>
              </td>
              <td>{{ item.item_ref }}</td>
              <td>
                <div
                  v-if="item.category_name"
                  class="badge badge-ghost badge-xs"
                >
                  {{ item.category_name }}
                </div>
                <div>{{ item.item_name }}</div>
              </td>
              <td v-html="nl2br(item.item_description)"></td>
              <td class="text-center">{{ item.quantity }}</td>
              <td class="text-center">
                {{ item.unit_price
                }}<span v-if="default_currency_symbol == client_currency">{{
                  default_currency_symbol
                }}</span>
                <span v-else>{{ client_currency }}</span>
              </td>
              <td class="text-center">{{ item.vat_rate }}%</td>
              <td class="text-center">
                {{ item.discount }}% <br />
                {{
                  calculateDiscountAmount(
                    item.quantity,
                    item.unit_price,
                    item.vat_rate,
                    item.discount
                  )
                }}
              </td>
              <td class="text-right">
                {{ item.total_amount }}
                <span v-if="default_currency_symbol == client_currency">{{
                  default_currency_symbol
                }}</span>
                <span v-else>{{ client_currency }}</span>
              </td>
              <td>
                <div v-if="invoice.status == 'draft'">
                  <span class="lg:tooltip" :data-tip="translations.edit">
                    <button
                      @click.prevent="editItem(item.id)"
                      class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-3 rounded"
                    >
                      <i class="far fa-edit"></i></button
                  ></span>
                  <span class="lg:tooltip" :data-tip="translations.delete">
                    <button
                      @click.prevent="confirmremoveItem(item.id, invoice.id)"
                      class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-3 mx-2 rounded"
                    >
                      <i v-if="!item.loading_del" class="far fa-trash-alt"></i>
                      <span
                        v-if="item.loading_del"
                        class="loading loading-spinner loading-xs"
                      ></span></button
                  ></span>
                </div>
              </td>
            </tr>
            <tr v-if="invoice.status == 'draft'">
              <td class="px-2"></td>
              <td class="align-top px-2">
                <div class="flex items-center border rounded-md relative">
                  <input
                    type="text"
                    v-model="newItem.item_ref"
                    @input="fetchRefs"
                    @focus="showDropdownRef = true"
                    :placeholder="translations.item_ref"
                    class="w-full p-2.5 input-xs outline-none"
                  />
                  <ul
                    v-if="showDropdownRef && refs.length"
                    class="autocomplete-dropdown bg-base-100"
                  >
                    <li
                      v-for="ref in refs"
                      :key="ref.ref"
                      @click="selectItem(ref)"
                      class="autocomplete-item"
                      v-html="highlightMatch(ref.ref)"
                    ></li>
                  </ul>
                </div>
              </td>
              <td class="align-top px-2">
                <select
                  class="select select-xs w-full mb-1 ecwp-select"
                  v-model="newItem.item_category"
                >
                  <option disabled selected>Type</option>
                  <option
                    v-for="category in categories"
                    :key="category.id"
                    :value="category.id"
                  >
                    {{ category.name }}
                  </option>
                </select>
                <div class="flex items-center border rounded-md relative">
                  <input
                    type="text"
                    v-model="newItem.item_name"
                    @input="fetchArticles"
                    @focus="showDropdown = true"
                    :placeholder="translations.item_name"
                    class="w-full p-2.5 bg-transparent input-xs outline-none"
                  />
                  <ul
                    v-if="showDropdown && articles.length"
                    class="autocomplete-dropdown bg-base-100"
                  >
                    <li
                      v-for="item in articles"
                      :key="item.name"
                      @click="selectItem(item)"
                      class="autocomplete-item bt-primary"
                      v-html="highlightMatch(item.name)"
                    ></li>
                  </ul>
                </div>
              </td>
              <td class="align-top">
                <div class="flex items-center rounded-md">
                  <textarea
                    v-model="newItem.item_description"
                    :placeholder="translations.item_description"
                    class="textarea textarea-bordered input-xs w-full"
                    @input="resize()"
                    ref="textarea"
                  ></textarea>
                </div>
              </td>
              <td class="align-top">
                <div class="flex items-center border rounded-lg">
                  <div class="inline-flex">
                    <div
                      class="select-none border py-3 px-2 cursor-pointer bg-base-300 hover:bg-gray-200 rounded-l"
                      @click="decrease"
                    >
                      -
                    </div>

                    <input
                      type="number"
                      min="1"
                      v-model="newItem.quantity"
                      :placeholder="translations.quantity"
                      class="w-full p-2.5 bg-transparent outline-none max-w-40"
                      @input="updateTotal"
                    />

                    <div
                      class="select-none border py-3 px-2 cursor-pointer bg-base-300 hover:bg-gray-200 rounded-r"
                      @click="increase"
                    >
                      +
                    </div>
                  </div>
                </div>
              </td>
              <td class="align-top">
                <div class="flex items-center border rounded-md">
                  <input
                    type="number"
                    v-model="newItem.unit_price"
                    :placeholder="translations.unit_price"
                    class="w-full p-2.5 bg-transparent outline-none max-w-40"
                    @input="updateTotal"
                  />
                </div>
              </td>
              <td class="align-top">
                <select
                  v-model="newItem.vat_rate"
                  @change="updateTotal"
                  class="select select-sm w-full mb-1 ecwp-select min-w-20"
                >
                  <option
                    v-for="rate in list_vats"
                    :key="rate"
                    :value="rate.rate"
                  >
                    {{ rate.rate }}%
                  </option>
                </select>
              </td>
              <td class="align-top">
                <div class="flex items-center border rounded-md">
                  <input
                    type="number"
                    v-model="newItem.discount"
                    min="0"
                    max="100"
                    :placeholder="translations.discount"
                    class="w-full p-2.5 bg-transparent outline-none max-w-40"
                    @input="updateTotal"
                  />
                  <div class="px-3 py-2.5 rounded-l-md bg-base-300 border-r">
                    %
                  </div>
                </div>
              </td>
              <td class="text-right">
                {{
                  calculateTotal(
                    newItem.quantity,
                    newItem.unit_price,
                    newItem.vat_rate,
                    newItem.discount
                  )
                }}
              </td>
              <td>
                <span class="lg:tooltip" :data-tip="translations.add">
                  <button
                    type="submit"
                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                  >
                    <i v-if="!loading_add" class="fa fa-plus"></i>
                    <span
                      v-if="loading_add"
                      class="loading loading-spinner loading-xs"
                    ></span></button
                ></span>
              </td>
            </tr>
            <tr>
              <td colspan="8" class="text-right no-border">
                <strong>{{ translations.subtotal }}</strong>
              </td>
              <td class="text-right">
                <span
                  v-if="totalAmount !== totalAmountWithoutDiscount"
                  class="line-through"
                >
                  {{ totalAmountWithoutDiscount }}
                </span>
                {{ totalAmount }}
              </td>
              <td></td>
            </tr>
            <template v-if="settings.vat_active == 1">
              <tr v-for="(rate, index) in getUniqueVATRates()" :key="index">
                <td colspan="8" class="text-right no-border">
                  <strong> {{ translations.tax }} ({{ rate }}%) </strong>
                </td>
                <td class="text-right">{{ calculateVATForRate(rate) }}</td>
                <td></td>
              </tr>
            </template>
            <tr>
              <td colspan="8" class="text-right no-border font-bold text-xl">
                <strong>{{ translations.total }}</strong>
              </td>
              <td class="text-right no-border font-bold text-xl">
                {{ calculateTotalAmountWithVAT() }}
              </td>
              <td></td>
            </tr>
            <tr v-if="client_currency != default_currency_symbol">
              <td colspan="8" class="text-right no-border">
                <strong>{{ translations.exchange_rate }}</strong>
              </td>
              <td class="text-right no-border">
                {{ invoice.exchange_rate }}
              </td>
            </tr>
            <tr v-if="client_currency != default_currency_symbol">
              <td colspan="8" class="text-right no-border">
                <strong
                  >{{ translations.total }}
                  {{ default_currency_symbol }}</strong
                >
              </td>
              <td class="text-right no-border font-bold text-xl">
                {{ totalAmountDefaultCurrency }}{{ default_currency_symbol }}
              </td>
            </tr>
          </tbody>
        </table>
      </form>
    </Card>
  </div>
</template>
  
<script>
import Card from "@/components/Card.vue";
import InvoiceNavBar from "@/components/invoices/NavBar.vue";
import EditItemModal from "@/components/invoices/Modal_Edit_Item.vue";
import RemoveModal from "@/components/RemoveAlert.vue";
import Sortable from "sortablejs";
import { fetchSettings } from "@/api/api";
import { parseDate } from "@/utils/helpers";

export default {
  name: "InvoiceViewDetail",
  components: {
    Card,
    InvoiceNavBar,
    EditItemModal,
    RemoveModal,
  },
  data() {
    return {
      selectedItem: null,
      SelectedInvoiceId: null,
      editItemsModal: false,
      loading: false,
      loading_add: false,
      invoice: [],
      invoiceItems: [],
      newItem: {
        loading_del: false,
        item_name: "",
        item_ref: "",
        item_category: "Type",
        item_description: "",
        quantity: 1,
        vat_rate: 0,
        unit_price: 0,
        discount: 0,
        total_price: 0,
        total_amount: 0,
      },
      settings: [],
      client_detail: [],
      list_vats: [],
      client_currency: "",
      default_vat: "",
      default_currency: "",
      default_currency_symbol: "",
      articles: [],
      categories: [],
      refs: [],
      showDropdown: false,
      showDropdownRef: false,
      toast: {
        visible: false,
        message: "",
        type: "alert-success",
        position: "toast-bottom toast-end",
      },
    };
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
    isInvoiceOverdue() {
      const dueDate = parseDate(
        this.invoice.due_date,
        this.settings.date_format
      );
      const today = new Date();
      return dueDate && dueDate < today;
    },
    defaultCurrency() {
      return {
        currency_id: this.settings.default_currency,
        currency_symbol: this.default_currency_symbol,
      };
    },
    clientCurrency() {
      return {
        currency_id: this.client_detail.currency_id,
        currency_symbol: this.client_currency,
      };
    },
    totalAmountWithoutDiscount() {
      const total = this.invoiceItems.reduce((total, item) => {
        const priceWithoutDiscount = item.quantity * item.unit_price;
        return total + priceWithoutDiscount;
      }, 0);
      return this.formatCurrency(total);
    },

    totalAmount() {
      const total = this.invoiceItems.reduce(
        (totalPrice, item) => totalPrice + parseFloat(item.total_price),
        0
      );
      return this.formatCurrency(total);
    },

    totalAmountDefaultCurrency() {
      let total = this.invoiceItems.reduce((totalAmount, item) => {
        return totalAmount + parseFloat(item.total_amount);
      }, 0);

      let total_amount = total;

      const total_amount_with_exchange =
        total_amount * this.invoice.exchange_rate;

      return total_amount_with_exchange.toFixed(2);
    },

    totalAmountWithVAT() {
      const totalAmount = parseFloat(this.totalAmount);
      if (this.settings.vat_active == 1) {
        const vatAmount = parseFloat(this.calculateVAT());
        return this.formatCurrency(totalAmount + vatAmount);
      } else {
        return this.formatCurrency(totalAmount);
      }
    },
  },
  methods: {
    getUniqueVATRates() {
      const vatRates = new Set();
      this.invoiceItems.forEach((item) => {
        if (item.vat_rate) {
          vatRates.add(item.vat_rate);
        }
      });
      return Array.from(vatRates);
    },
    calculateVATForRate(rate) {
      let totalVAT = 0;
      this.invoiceItems.forEach((item) => {
        if (item.vat_rate === rate) {
          const totalBeforeVAT = item.quantity * item.unit_price;
          const discountAmount = (totalBeforeVAT * item.discount) / 100;
          const totalAfterDiscount = totalBeforeVAT - discountAmount;
          const vatAmount = (totalAfterDiscount * rate) / 100;
          totalVAT += vatAmount;
        }
      });
      return this.formatCurrency(totalVAT);
    },

    calculateTotalAmountWithVAT() {
      let total = this.invoiceItems.reduce((totalAmount, item) => {
        return totalAmount + parseFloat(item.total_amount);
      }, 0);
      return this.formatCurrency(total);
    },
    fetchInvoice() {
      this.loading = true;
      fetch(`/wp-json/my-easy-compta/v1/invoices/${this.$route.params.id}`, {
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          if (data) {
            this.invoice = data;
            this.fetchClientInfo(data.client_id);
          } else {
            console.error("Invoice not found");
          }
        })
        .catch((error) => {
          console.error("Error fetching invoice:", error);
          this.loading = false;
        });
    },
    fetchClientInfo(clientId) {
      this.loading = true;
      fetch(`/wp-json/my-easy-compta/v1/clients/details/${clientId}`, {
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error("Client not found");
          }
          return response.json();
        })
        .then((data) => {
          this.client_detail = data;
          const currencyId = data.currency_id;
          if (currencyId) {
            this.fetchCurrencyDetails(currencyId);
          }
        })
        .catch((error) => {
          console.error("Error fetching client info:", error);
          this.loading = false;
        });
    },
    fetchItems() {
      this.loading = true;
      fetch(
        `/wp-json/my-easy-compta/v1/invoices/${this.$route.params.id}/items`,
        {
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
        }
      )
        .then((response) => response.json())
        .then((data) => {
          if (data.code == "no_items_found") {
            console.error("No items found");
            this.invoiceItems = [];
            this.loading = false;
          } else {
            this.invoiceItems = data;
            this.loading = false;
          }
        })
        .catch((error) => {
          console.error("Error fetching items:", error);
          this.loading = false;
        });
    },
    fetchCurrencyDetails(currencyId) {
      this.loading = true;
      fetch(`/wp-json/my-easy-compta/v1/settings/currency/${currencyId}`, {
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => {
          if (!response.ok) {
            this.loading = false;
            throw new Error("Currency details not found");
          }
          this.loading = false;
          return response.json();
        })
        .then((data) => {
          this.client_currency = data.symbol;
          this.loading = false;
        })
        .catch((error) => {
          this.loading = false;
          console.error("Error fetching currency details:", error);
        });
    },
    updateTotal() {
      const totalBeforeDiscount =
        this.newItem.quantity * this.newItem.unit_price;
      const discountAmount =
        (totalBeforeDiscount * this.newItem.discount) / 100;
      const totalAfterDiscount = totalBeforeDiscount - discountAmount;
      const vatAmount = (totalAfterDiscount * this.newItem.vat_rate) / 100;
      const totalAmountWithVAT = totalAfterDiscount + vatAmount;
      this.newItem.total_price = this.formatCurrency(totalAfterDiscount);
      this.newItem.total_amount = this.formatCurrency(totalAmountWithVAT);
    },
    calculateTotal(quantity, unitPrice, vat_rate, discount) {
      const totalBeforeDiscount = quantity * unitPrice;
      const discountAmount = (totalBeforeDiscount * discount) / 100;
      const totalAfterDiscount = totalBeforeDiscount - discountAmount;
      const taxAmount = (totalAfterDiscount * vat_rate) / 100;
      const total = totalAfterDiscount + taxAmount;
      return this.formatCurrency(total);
    },
    submitItems() {
      this.updateTotal();
      const newElement = {
        ...this.newItem,
        invoice_id: this.$route.params.id,
      };
      this.loading_add = true;

      fetch("/wp-json/my-easy-compta/v1/invoices/element-add", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
        body: JSON.stringify(newElement),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            this.loading_add = false;
            this.fetchItems();
            this.newItem = {
              item_name: "",
              item_ref: "",
              item_category: "Type",
              item_description: "",
              quantity: 1,
              vat_rate: this.default_vat.rate,
              unit_price: 0,
              discount: 0,
              total_price: 0,
              total_amount: 0,
            };
          } else {
            this.showToast(data.message, "alert-error");
            console.error("Error submitting item:", data.message);
            this.loading_add = false;
          }
        })
        .catch((error) => {
          this.showToast(error.message, "alert-error");
          console.error("Error submitting item:", error);
          this.loading_add = false;
        });
    },
    increase() {
      this.newItem.quantity++;
    },
    decrease() {
      if (this.newItem.quantity > 1) {
        this.newItem.quantity--;
      }
    },
    confirmremoveItem(itemId, invoiceId) {
      this.selectedItem = itemId;
      this.SelectedInvoiceId = invoiceId;
      modal_remove.showModal();
      this.showRemoveModal = true;
    },
    removeItem(itemId, invoiceId) {
      const itemToRemove = this.invoiceItems.find((item) => item.id === itemId);
      itemToRemove.loading_del = true;
      fetch(`/wp-json/my-easy-compta/v1/invoices/element-delete/${itemId}`, {
        method: "DELETE",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
        body: JSON.stringify({ invoice_id: invoiceId }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            itemToRemove.loading_del = false;
            this.fetchItems();
          } else {
            this.showToast(data.message, "alert-error");
            console.error("Error removing item:", data.message);
            itemToRemove.loading_del = false;
          }
        })
        .catch((error) => {
          this.showToast(error.message, "alert-error");
          console.error("Error removing item:", error);
          itemToRemove.loading_del = false;
        });
    },
    editItem(itemID) {
      this.loadingModal = true;
      this.editItemsModal = true;
      modal_edit_item.showModal();
      this.fetchItemDetails(itemID);
    },
    fetchItemDetails(itemID) {
      fetch(`/wp-json/my-easy-compta/v1/invoices/item-details/${itemID}`, {
        headers: {
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          this.selectedItem = data;
          this.loading = false;
        })
        .catch((error) => {
          console.error("Error fetching item details:", error);
          this.loading = false;
        });
    },
    formatCurrency(amount) {
      if (isNaN(amount)) return "0,00";

      const formattedAmount = amount
        .toFixed(2)
        .replace(/\B(?=(\d{3})+(?!\d))/g, " ");

      const currencySymbol =
        this.client_currency !== this.default_currency_symbol
          ? this.client_currency
          : this.default_currency_symbol;
      return `${formattedAmount} ${currencySymbol}`;
    },
    calculateDiscountAmount(quantity, unitPrice, vat_rate, discount) {
      const totalBeforeDiscount = quantity * unitPrice;
      const discountAmount = (totalBeforeDiscount * discount) / 100;
      const taxAmount = (discountAmount * vat_rate) / 100;
      const totalDiscountAmount = discountAmount + taxAmount;
      return this.formatCurrency(totalDiscountAmount);
    },
    calculateVAT() {
      const totalAmount = parseFloat(this.totalAmount);
      const defaultVAT = parseFloat(this.default_vat.rate);
      const vatAmount = totalAmount * (defaultVAT / 100);
      return this.formatCurrency(vatAmount);
    },
    onDragEnd(event) {
      const draggedItem = this.invoiceItems[event.oldIndex];
      this.invoiceItems.splice(event.oldIndex, 1);
      this.invoiceItems.splice(event.newIndex, 0, draggedItem);
      const order = this.invoiceItems.map((item) => item.id);

      this.saveOrderToDatabase(order);
    },
    nl2br(text) {
      if (!text) return "";
      return text.replace(/\n/g, "<br>");
    },
    resize() {
      let element = this.$refs["textarea"];

      element.style.height = "auto";
      element.style.height = element.scrollHeight + "px";
    },
    saveOrderToDatabase(order) {
      fetch("/wp-json/my-easy-compta/v1/invoices/update-invoice-items-order", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
        body: JSON.stringify({ order: order }),
      })
        .then((response) => {
          if (response.ok) {
            console.log("Order saved successfully.");
          } else {
            console.error("Failed to save order:", response.statusText);
          }
        })
        .catch((error) => {
          console.error("Error saving order:", error);
        });
    },
    fetchCategoriesArticles() {
      fetch(`/wp-json/my-easy-compta/v1/categories-articles`, {
        headers: {
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          this.categories = data;
        })
        .catch((error) => console.error("Error fetching categories:", error));
    },
    fetchArticles() {
      if (this.newItem.item_name.length < 1) {
        this.articles = [];
        return;
      }
      fetch(
        `/wp-json/my-easy-compta/v1/articles?search=${this.newItem.item_name}&method=name`,
        {
          headers: {
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
        }
      )
        .then((response) => response.json())
        .then((data) => {
          this.articles = data;
        })
        .catch((error) => console.error("Error fetching articles:", error));
    },
    selectItem(item) {
      this.newItem.item_ref = item.ref;
      this.newItem.item_name = item.name;
      this.newItem.item_description = item.description;
      this.newItem.unit_price = item.unit_price;
      this.showDropdown = false;
      this.showDropdownRef = false;
    },
    fetchRefs() {
      if (this.newItem.item_ref.length < 1) {
        this.refs = [];
        return;
      }
      fetch(
        `/wp-json/my-easy-compta/v1/articles?search=${this.newItem.item_ref}&method=ref`,
        {
          headers: {
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
        }
      )
        .then((response) => response.json())
        .then((data) => {
          this.refs = data;
        })
        .catch((error) => console.error("Error fetching refrences:", error));
    },
    async loadSettings() {
      try {
        this.loadingPrice = true;
        const { settings, currencySymbol, vatData, listVatData } =
          await fetchSettings();
        this.settings = settings;
        this.default_currency_symbol = currencySymbol;
        this.default_vat = vatData;
        this.list_vats = listVatData;
        this.newItem.vat_rate = this.default_vat.rate;
        this.loadingPrice = false;
      } catch (error) {
        this.showToast(error.message, "alert-error");
        this.loadingPrice = false;
      }
    },
    highlightMatch(text) {
      if (!this.newItem.item_name) return text;
      const regex = new RegExp(
        `(${this.escapeRegExp(this.newItem.item_name)})`,
        "gi"
      );
      return text.replace(regex, "<b>$1</b>");
    },
    escapeRegExp(string) {
      return string.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    },
    handleClickOutside(event) {
      if (!this.$el.contains(event.target)) {
        this.showDropdown = false;
        this.showDropdownRef = false;
      }
    },
    showToast(message, type) {
      this.toast.message = message;
      this.toast.type = type;
      this.toast.visible = true;
      setTimeout(() => {
        this.toast.visible = false;
      }, 3000);
    },
  },
  beforeDestroy() {
    document.removeEventListener("click", this.handleClickOutside);
  },
  mounted() {
    this.fetchInvoice();
    this.fetchItems();
    this.loadSettings();
    this.fetchCategoriesArticles();
    document.addEventListener("click", this.handleClickOutside);

    const tbody = document.querySelector("tbody");
    Sortable.create(tbody, {
      animation: 150,
      handle: ".drag-handle",
      onEnd: this.onDragEnd,
    });
  },
};
</script>
