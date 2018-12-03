import Vue from 'vue'
import Vuex from 'vuex'
import api from '@/utils/api'
import BDefined from '@/utils/BDefined'

Vue.use(Vuex);

const store = new Vuex.Store({
  state: {
    // 首页数据
    newGoods: [],
    hotGoods: [],
    topics: [],
    brands: [],
    floorGoods: [],
    banner: [],
    channel: []
  },
  mutations: {
    getIndexData (state, res) {
      state.newGoods = res.newGoodsList;
      state.hotGoods = res.hotGoodsList;
      // state.topics = res.topicList;
      state.brands = res.brandList;
      state.floorGoods = res.categoryList;
      state.banner = res.banner;
      // state.channel = res.channel;
    }
  },
  actions: {
    async getIndexData ({ commit }) {
      var res = {};
      const goodsList = await api.getGoodsList();
      if (goodsList.code !== BDefined.responseSuccessCode) return;
      res.newGoodsList = goodsList.data.goods_list;
      res.hotGoodsList = goodsList.data.goods_list;

      const goodsAdvertise = await api.getGoodsAdvertise();
      if (goodsAdvertise.code !== BDefined.responseSuccessCode) return;
      res.banner = goodsAdvertise.data;

      const goodsCategory = await api.getGoodsCategory();
      if (goodsCategory.code !== BDefined.responseSuccessCode) return;
      res.categoryList = goodsCategory.data;

      const brandList = await api.getBrandList();
      if (brandList.code !== BDefined.responseSuccessCode) return;
      res.brandList = brandList.data;

      commit('getIndexData', res)
    }
  }
});

export default store
